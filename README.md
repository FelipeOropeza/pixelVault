# 🌌 PixelVault

**PixelVault** é uma plataforma premium de gerenciamento de arquivos e documentos, construída com oque há de mais moderno no ecossistema Laravel. Focada em uma experiência de usuário (UX) excepcional, a aplicação oferece uma interface fluida, reativa e visualmente deslumbrante.

## 🚀 Tecnologias Core

O projeto utiliza as versões mais recentes das tecnologias líderes de mercado:

*   **Framework:** Laravel 13
*   **Reatividade:** Livewire 4 (SFC - Single File Components)
*   **UI Engine:** [Flux UI](https://fluxui.dev/)
*   **Styling:** Tailwind CSS com estética Glassmorphism

## ✨ Funcionalidades Principais

*   **Gerenciamento Inteligente:** Organize arquivos em pastas e subpastas de forma intuitiva.
*   **Navegação por Breadcrumbs:** Trilha de navegação interativa com suporte a *Drag & Drop* para movimentação rápida entre níveis.
*   **Visualização Flexível:** Alterne instantaneamente entre visualização em **Grade** (cards com preview) e **Lista** (tabela detalhada).
*   **Upload Moderno:** Arraste arquivos diretamente do seu computador para qualquer lugar da tela para iniciar o upload.
*   **Preview de Imagens:** Visualize suas imagens em tela cheia com um clique duplo rápido.
*   **Sistema de Lixeira:** Exclusão segura com suporte a `SoftDeletes`, permitindo restaurar arquivos ou pastas acidentalmente removidas.
*   **Gestão de Armazenamento:** Painel visual na barra lateral indicando o uso total de armazenamento da conta.
*   **Ações em Massa:** Selecione múltiplos arquivos para favoritar, excluir ou mover simultaneamente usando a barra de ferramentas flutuante.
*   **Movimentação por Drag & Drop:** Organize sua biblioteca arrastando arquivos diretamente para dentro de pastas na visualização principal.
*   **Favoritos:** Marque seus documentos mais importantes para acesso rápido através da seção dedicada.

## 🛠️ Requisitos e Instalação

### Pré-requisitos
- PHP 8.5+
- Composer
- Node.js & NPM
- Banco de Dados (SQLite, MySQL ou PostgreSQL)

### Passos para Instalação

1.  **Clone o repositório:**
    ```bash
    git clone https://github.com/FelipeOropeza/pixelVault.git
    cd pixelVault
    ```

2.  **Instale as dependências:**
    ```bash
    composer install
    npm install
    ```

3.  **Configure o ambiente:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Execute as migrações:**
    ```bash
    php artisan migrate --seed
    ```

5.  **Inicie o servidor de desenvolvimento:**
    ```bash
    composer run dev
    # ou
    php artisan serve & npm run dev
    ```

## 📂 Estrutura de Componentes

O projeto segue uma arquitetura modular utilizando componentes Blade e Livewire 4:

- `⚡dashboard.blade.php`: Componente principal do cofre de arquivos.
- `folder-card` & `file-card`: Representação visual em modo grade.
- `folder-row` & `file-row`: Representação visual em modo lista.
- `selection-bar`: Barra flutuante premium para ações em massa.
- `sidebar-nav`: Navegação lateral e metadados de armazenamento.

---

Desenvolvido com ❤️ por [Felipe Carvalho](https://github.com/FelipeOropeza)
