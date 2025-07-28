const { makeWASocket, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys')
const axios = require('axios')
const { Boom } = require('@hapi/boom')
const qrcode = require('qrcode-terminal')
const fs = require('fs')
const mime = require('mime-types')

const CONFIG_URL = 'https://meudrivenet.x10.bz/botzap1/config.json'
const GRUPOS_JSON = './grupos.json' // salvar localmente
const WEBHOOK_URL = 'https://meudrivenet.x10.bz/botzap1/webhook.php'

let configCache = null
let lastConfigLoad = 0
const CACHE_DURATION = 5 * 60 * 1000 // 5 minutos

async function loadConfig() {
    const now = Date.now()
    if (configCache && now - lastConfigLoad < CACHE_DURATION) {
        return configCache
    }
    try {
        const res = await axios.get(CONFIG_URL)
        configCache = res.data
        lastConfigLoad = now
        return configCache
    } catch (e) {
        console.error('⚠️ Erro ao carregar config.json via HTTP, usando padrão local')
        return { responder_usuarios: true, grupos_autorizados: [] }
    }
}

async function startBot() {
    const { state, saveCreds } = await useMultiFileAuthState('auth')
    const sock = makeWASocket({ auth: state })

    sock.ev.on('creds.update', saveCreds)

    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update

        if (qr) {
            console.log("📲 Escaneie o QR abaixo com o WhatsApp:")
            qrcode.generate(qr, { small: true })
        }

        if (connection === 'close') {
            const reason = new Boom(lastDisconnect?.error)?.output.statusCode
            if (reason === DisconnectReason.loggedOut) {
                console.log("❌ Sessão expirada. Escaneie novamente.")
            } else {
                console.log("🔁 Reconectando em 5 segundos...")
                setTimeout(() => startBot(), 5000)
            }
        }

        if (connection === 'open') {
            console.log("✅ Bot conectado com sucesso!")
            try {
                const chats = await sock.groupFetchAllParticipating()
                const grupos = {}

                for (const jid in chats) {
                    grupos[jid] = chats[jid].subject
                }

                fs.writeFileSync(GRUPOS_JSON, JSON.stringify(grupos, null, 2))
                console.log(`📂 Lista de grupos salva em ${GRUPOS_JSON}`)
                console.log("📌 Atualize config.json com os grupos autorizados que deseja responder")
            } catch (err) {
                console.error('❌ Erro ao salvar grupos:', err.message)
            }
        }
    })

    sock.ev.on('contacts.update', () => {
        // pode ser usado para atualizar contatos em cache se quiser
    })

    sock.ev.on('messages.upsert', async ({ messages }) => {
        const msg = messages[0]
        if (!msg.message || msg.key.fromMe) return

        const sender = msg.key.remoteJid
        const text = msg.message.conversation || msg.message.extendedTextMessage?.text
        if (!text) return

        const config = await loadConfig()
        const isGroup = sender.endsWith('@g.us')
        const autorizado = isGroup
            ? config.grupos_autorizados.includes(sender)
            : config.responder_usuarios

        if (!autorizado) return

        // Buscar nome do contato na lista de contatos cacheada
        let nomeContato = sender.split('@')[0]
        try {
            const contacts = await sock.fetchContacts()
            if (contacts[sender]?.notify) {
                nomeContato = contacts[sender].notify
            }
        } catch (e) {
            // ignorar erro
        }

        try {
            const res = await axios.post(WEBHOOK_URL, {
                number: sender,
                message: text
            })

            if (res.data.reply) {
                const resposta = res.data.reply.replace(/{nome}/gi, nomeContato)
                await sock.sendMessage(sender, { text: resposta })
            }

            if (res.data.file_base64 && res.data.filename) {
                const buffer = Buffer.from(res.data.file_base64, 'base64')
                const mimetype = mime.lookup(res.data.filename) || 'application/octet-stream'

                if (mimetype.startsWith('image/')) {
                    await sock.sendMessage(sender, {
                        image: buffer,
                        mimetype,
                        caption: res.data.caption || '🖼️ Aqui está sua imagem'
                    })
                } else if (mimetype.startsWith('video/')) {
                    await sock.sendMessage(sender, {
                        video: buffer,
                        mimetype,
                        caption: res.data.caption || '📹 Aqui está seu vídeo'
                    })
                } else {
                    await sock.sendMessage(sender, {
                        document: buffer,
                        mimetype,
                        fileName: res.data.filename
                    })
                }
            }

        } catch (err) {
            console.error('❌ Erro no webhook:', err.message)
        }
    })
}

startBot()