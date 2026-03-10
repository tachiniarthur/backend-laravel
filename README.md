# 🛒 Backend Laravel — API E-commerce

API backend de um e-commerce construída com **Laravel 12**, **PHP 8.2+** e **SQLite**. Utiliza **Laravel Sanctum** para autenticação via tokens.

---

## 📋 Índice

- [Pré-requisitos](#-pré-requisitos)
- [Instalação dos pré-requisitos](#-instalação-dos-pré-requisitos)
    - [Windows](#windows)
    - [Linux (Ubuntu/Debian)](#linux-ubuntudebian)
    - [macOS](#macos)
- [Clonando o projeto](#-clonando-o-projeto)
- [Configuração do projeto](#-configuração-do-projeto)
- [Rodando o projeto](#-rodando-o-projeto)
- [Endpoints da API](#-endpoints-da-api)
- [Usuário admin padrão](#-usuário-admin-padrão)
- [Comandos úteis](#-comandos-úteis)

---

## ✅ Pré-requisitos

Antes de começar, você precisa ter instalado na sua máquina:

| Ferramenta   | Versão mínima |
| ------------ | ------------- |
| **PHP**      | 8.2           |
| **Composer** | 2.x           |
| **Node.js**  | 18.x          |
| **npm**      | 9.x           |
| **Git**      | 2.x           |

> **Extensões PHP obrigatórias:** `mbstring`, `xml`, `curl`, `sqlite3`, `pdo_sqlite`, `fileinfo`, `openssl`, `tokenizer`, `bcmath`

---

## 📦 Instalação dos pré-requisitos

### Windows

#### 1. Instalar o PHP

A maneira mais fácil é usando o [XAMPP](https://www.apachefriends.org/pt_br/index.html) ou instalando diretamente:

1. Baixe o PHP em: https://windows.php.net/download/ (escolha a versão **VS16 x64 Thread Safe**)
2. Extraia para `C:\php`
3. Adicione `C:\php` à variável de ambiente `PATH`:
    - Pesquise "variáveis de ambiente" no menu Iniciar
    - Em **Variáveis do sistema**, edite `Path` e adicione `C:\php`
4. Renomeie `php.ini-development` para `php.ini`
5. Abra o `php.ini` e descomente (remova o `;`) as linhas:
    ```ini
    extension=curl
    extension=fileinfo
    extension=mbstring
    extension=openssl
    extension=pdo_sqlite
    extension=sqlite3
    extension=bcmath
    ```
6. Verifique:
    ```bash
    php -v
    ```

#### 2. Instalar o Composer

1. Baixe e execute o instalador: https://getcomposer.org/Composer-Setup.exe
2. Verifique:
    ```bash
    composer -V
    ```

#### 3. Instalar o Node.js e npm

1. Baixe e instale o Node.js LTS: https://nodejs.org/
2. Verifique:
    ```bash
    node -v
    npm -v
    ```

#### 4. Instalar o Git

1. Baixe e instale: https://git-scm.com/download/win
2. Verifique:
    ```bash
    git --version
    ```

---

### Linux (Ubuntu/Debian)

```bash
# Atualizar pacotes
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2 e extensões necessárias
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-mbstring php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-bcmath php8.2-tokenizer php8.2-fileinfo unzip curl git

# Verificar PHP
php -v

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Verificar Composer
composer -V

# Instalar Node.js (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs

# Verificar Node.js e npm
node -v
npm -v
```

---

### macOS

```bash
# Instalar Homebrew (se ainda não tiver)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Instalar PHP
brew install php

# Verificar PHP
php -v

# Instalar Composer
brew install composer

# Verificar Composer
composer -V

# Instalar Node.js
brew install node

# Verificar Node.js e npm
node -v
npm -v
```

---

## 📥 Clonando o projeto

```bash
git clone https://github.com/tachiniarthur/backend-laravel.git
cd backend-laravel
```

---

## ⚙️ Configuração do projeto

### Opção 1: Setup automático (recomendado)

O projeto possui um script de setup que faz tudo automaticamente:

```bash
composer setup
```

Esse comando irá:

- Instalar as dependências PHP (`composer install`)
- Copiar o `.env.example` para `.env` (se não existir)
- Gerar a chave da aplicação (`php artisan key:generate`)
- Executar as migrations do banco de dados
- Instalar as dependências Node.js (`npm install`)
- Compilar os assets (`npm run build`)

### Opção 2: Setup manual (passo a passo)

Se preferir fazer manualmente:

```bash
# 1. Instalar dependências PHP
composer install

# 2. Copiar o arquivo de variáveis de ambiente
cp .env.example .env

# 3. Gerar a chave da aplicação
php artisan key:generate

# 4. Criar o banco de dados SQLite
touch database/database.sqlite

# 5. Executar as migrations (criar as tabelas)
php artisan migrate

# 6. (Opcional) Popular o banco com dados iniciais (cria o usuário admin)
php artisan db:seed

# 7. Criar o link simbólico para o storage (upload de imagens)
php artisan storage:link

# 8. Instalar dependências Node.js
npm install

# 9. Compilar os assets
npm run build
```

---

## 🚀 Rodando o projeto

### Modo simples (apenas o servidor)

```bash
php artisan serve
```

A API ficará disponível em: **http://localhost:8000**

### Modo desenvolvimento completo (servidor + queue + logs + vite)

```bash
composer dev
```

Esse comando inicia simultaneamente:

- 🌐 **Servidor Laravel** na porta 8000
- 📨 **Queue listener** para processar filas
- 📋 **Pail** para visualizar logs em tempo real
- ⚡ **Vite** para compilação de assets em tempo real

### Usando o script personalizado (com limites de upload aumentados)

```bash
bash scripts/serve.sh
```

---

## 🔗 Endpoints da API

A URL base da API é: `http://localhost:8000/api`

### Rotas públicas

| Método | Endpoint              | Descrição                  |
| ------ | --------------------- | -------------------------- |
| POST   | `/api/login`          | Fazer login                |
| POST   | `/api/create-account` | Criar uma conta            |
| GET    | `/api/products`       | Listar todos os produtos   |
| GET    | `/api/products/{id}`  | Ver detalhes de um produto |

### Rotas protegidas (necessário token de autenticação)

> Envie o token no header: `Authorization: Bearer {seu_token}`

| Método | Endpoint         | Descrição                   |
| ------ | ---------------- | --------------------------- |
| POST   | `/api/logout`    | Fazer logout                |
| GET    | `/api/user`      | Ver perfil do usuário       |
| PUT    | `/api/user`      | Atualizar perfil do usuário |
| GET    | `/api/cart`      | Ver itens do carrinho       |
| POST   | `/api/cart`      | Adicionar item ao carrinho  |
| PUT    | `/api/cart/{id}` | Atualizar item do carrinho  |
| DELETE | `/api/cart/{id}` | Remover item do carrinho    |
| GET    | `/api/orders`    | Listar pedidos do usuário   |
| POST   | `/api/orders`    | Criar um novo pedido        |

### Rotas de administrador

| Método | Endpoint                        | Descrição                     |
| ------ | ------------------------------- | ----------------------------- |
| POST   | `/api/products`                 | Criar produto                 |
| POST   | `/api/products/{id}`            | Atualizar produto             |
| DELETE | `/api/products/{id}`            | Remover produto               |
| GET    | `/api/admin/orders`             | Listar todos os pedidos       |
| PATCH  | `/api/admin/orders/{id}/status` | Atualizar status de um pedido |

---

## 👤 Usuário admin padrão

Ao rodar o `php artisan db:seed`, é criado um usuário administrador com as seguintes credenciais:

| Campo    | Valor             |
| -------- | ----------------- |
| Nome     | Admin             |
| Username | admin             |
| Email    | admin@example.com |
| Senha    | 123456            |

---

## 🛠️ Comandos úteis

```bash
# Rodar os testes
composer test

# Rodar as migrations novamente (apaga e recria as tabelas)
php artisan migrate:fresh

# Rodar migrations + seed (apaga, recria e popula)
php artisan migrate:fresh --seed

# Limpar caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Ver todas as rotas da API
php artisan route:list

# Abrir o console interativo (Tinker)
php artisan tinker
```

---

## 📁 Estrutura do projeto

```
├── app/
│   ├── Http/Controllers/    # Controllers da API
│   ├── Models/              # Models (User, Product, Order, CartItem, etc.)
│   ├── Providers/           # Service Providers
│   └── Services/            # Camada de serviços (lógica de negócio)
├── config/                  # Arquivos de configuração
├── database/
│   ├── factories/           # Factories para testes
│   ├── migrations/          # Migrations do banco de dados
│   └── seeders/             # Seeders (dados iniciais)
├── routes/
│   └── api.php              # Definição das rotas da API
├── storage/                 # Arquivos gerados (logs, uploads, cache)
├── tests/                   # Testes automatizados
├── .env.example             # Exemplo de variáveis de ambiente
├── composer.json            # Dependências PHP
└── package.json             # Dependências Node.js
```

---

## 📝 Tecnologias utilizadas

- **[Laravel 12](https://laravel.com/)** — Framework PHP
- **[Laravel Sanctum](https://laravel.com/docs/sanctum)** — Autenticação via tokens
- **[SQLite](https://www.sqlite.org/)** — Banco de dados
- **[Vite](https://vitejs.dev/)** — Bundler de assets

---

## 📄 Licença

Este projeto está licenciado sob a licença [MIT](https://opensource.org/licenses/MIT).
