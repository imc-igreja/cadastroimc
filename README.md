# Sistema de Carteirinha Ministerial

Sistema completo para cadastro de ministros e geração de carteirinhas em PDF com QR code dinâmico.

## 🚀 Funcionalidades

- ✅ Cadastro e edição de ministros
- ✅ Upload de fotos com crop automático
- ✅ Geração de PDF com todos os campos preenchidos
- ✅ QR code dinâmico com link único para cada carteirinha
- ✅ Listagem com busca em tempo real
- ✅ Modal customizado para confirmação de exclusão
- ✅ Design responsivo

## 📋 Requisitos

- PHP 8.0+
- PostgreSQL (Supabase)
- Python 3.8+
- Extensões PHP: `pdo_pgsql`, `gd`, `fileinfo`
- Bibliotecas Python: `reportlab`, `pypdf`, `Pillow`, `qrcode`

## 🔧 Instalação Local

1. Clone o repositório
2. Copie `.env.example` para `.env` e configure
3. Instale dependências Python:
```bash
pip install reportlab pypdf Pillow qrcode[pil]
```
4. Configure permissões das pastas:
```bash
chmod 755 uploads/ pdfs/
```
5. Inicie o servidor PHP:
```bash
php -S localhost:8080
```

## 🌐 Deploy em Hospedagem

### Hostinger / Hostgator

1. Faça upload via FTP de todos os arquivos
2. Configure o banco PostgreSQL no painel
3. Edite `config.php` com as credenciais
4. Instale Python e bibliotecas via SSH:
```bash
pip3 install --user reportlab pypdf Pillow qrcode[pil]
```
5. Configure permissões: `chmod 755 uploads/ pdfs/`

### Railway / Render

1. Conecte seu repositório Git
2. Configure variáveis de ambiente do `.env`
3. Adicione buildpack Python
4. Deploy automático

## 📁 Estrutura

```
├── index.php          # Formulário de cadastro
├── listar.php         # Lista de ministros
├── editar.php         # Edição de ministro
├── gerar_pdf.php      # Geração do PDF
├── gerar_carteirinha.py  # Script Python
├── config.php         # Configurações
├── uploads/           # Fotos dos ministros
└── pdfs/              # PDFs gerados
```

## 🔒 Segurança

- Nunca commite o arquivo `config.php` com credenciais reais
- Use `.env` para variáveis sensíveis em produção
- Configure HTTPS na hospedagem
- Valide uploads de imagem

## 📝 Licença

Desenvolvido para Igreja Missões em Cristo
