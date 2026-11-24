# CLAUDE.md

Guidance for Claude Code when working on **CF7 to Webhook**, a WordPress plugin (slug `cf7-to-zapier`).

## Read CONTRIBUTING.md first

Read [CONTRIBUTING.md](CONTRIBUTING.md) before writing code or committing, and follow it. It is the source of truth for:

- **Commit messages**: `[Tag] Relevant title`, in English, with tags such as `[Feat]`, `[Fix]`, `[Style]`, `[Refact]`, `[Docs]`, `[Test]` and `[Build]`. Do not use Conventional Commits (`feat:`, `docs:`, `chore:`).
- **Coding standards**: WordPress Coding Standards, indented with spaces.
- **Git workflow**: rebase on `master` before merging, and open an issue for each PR.
- **Webhook templates** in `ci/website/public/templates.json`, served by the static site in `ci/website`.

## Never edit README.md

`README.md` is generated from `readme.txt` by the [WP Readme to Markdown](https://github.com/mariovalney/vscode-wordpress-readme-to-markdown) VS Code extension, which converts the file every time `readme.txt` is saved. Anything written directly to `README.md` is overwritten.

- To change the description, FAQ, changelog, upgrade notice or header fields (`Stable tag`, `Tested up to`, etc.), edit `readme.txt` only, using the WordPress.org readme format.
- Do not create, regenerate or edit `README.md`. The maintainer regenerates it from VS Code.
