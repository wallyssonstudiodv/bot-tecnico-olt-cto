# 🤖 Bot Técnico OLT & CTO

Este projeto foi desenvolvido por **Wallysson S. de Oliveira** com o objetivo de ajudar técnicos de redes ópticas (FTTH) a identificarem rapidamente as informações das OLTs e CTOs em campo, diretamente pelo WhatsApp.

## 📌 Funcionalidades

- 🔍 Consulta rápida de OLT e perfil por número de CTO.
- 📥 Cadastro e edição de dados via painel administrativo.
- 🟢 Resposta automatizada via bot no WhatsApp.
- 🛠️ Integração com o [Baileys](https://github.com/WhiskeySockets/Baileys) para conexão e automação com o WhatsApp Web.
- 🌐 Interface web com visual estilo hacker e responsiva para celulares.
- 💾 Armazenamento local dos dados em arquivos JSON.

## 🧠 Como funciona

1. O técnico envia o número da CTO para o bot via WhatsApp.
2. O bot retorna automaticamente as informações da OLT e perfil configurados.
3. Os dados são administrados por um painel web simples e prático.

## 🚀 Tecnologias Utilizadas

- Node.js
- Baileys (API WhatsApp Web)
- Express
- HTML/CSS (tema neon hacker)
- JavaScript
- JSON (para armazenamento de dados)

## 📦 Instalação

```bash
git clone https://github.com/wallyssonstudiodv/bot-tecnico-olt-cto
cd bot-tecnico-olt-cto
npm install
node index.js