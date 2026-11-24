# CLAUDE.md

Guidance for Claude Code when working on **CF7 to Webhook**, a WordPress plugin (slug `cf7-to-zapier`).

## Read CONTRIBUTING.md first

Read [CONTRIBUTING.md](CONTRIBUTING.md) before writing code or committing, and follow it. It is the source of truth for:

- **Commit messages**: `[Tag] Relevant title`, in English, with tags such as `[Feat]`, `[Fix]`, `[Style]`, `[Refact]`, `[Docs]`, `[Test]` and `[Build]`. Do not use Conventional Commits (`feat:`, `docs:`, `chore:`).
- **Coding standards**: WordPress Coding Standards, indented with spaces.
- **Git workflow**: rebase on `master` before merging, and open an issue for each PR.
- **Webhook templates**: the format of each entry in `templates.json`.

## Never edit README.md

`README.md` is generated from `readme.txt` by the [WP Readme to Markdown](https://github.com/mariovalney/vscode-wordpress-readme-to-markdown) VS Code extension, which converts the file every time `readme.txt` is saved. Anything written directly to `README.md` is overwritten.

- To change the description, FAQ, changelog, upgrade notice or header fields (`Stable tag`, `Tested up to`, etc.), edit `readme.txt` only, using the WordPress.org readme format.
- Do not create, regenerate or edit `README.md`. The maintainer regenerates it from VS Code. When a change touches `readme.txt`, ask them to save it before committing, so both files go in the same commit.

## WordPress agent skills

`.claude/skills/` has four skills from [WordPress/agent-skills](https://github.com/WordPress/agent-skills) (GPL-2.0-or-later), copied from upstream commit `7374e6f` as its "Manual installation" describes. Claude Code loads them by itself:

- `wp-plugin-development`: hooks, admin UI, options, cron and the security baseline (nonces paired with capability checks, sanitized input, escaped output, AJAX handlers). Upstream has no separate security skill; security lives here, in `references/security.md`.
- `wp-plugin-directory-guidelines`: the WordPress.org guidelines, GPL compatibility, naming rules and Plugin Check. Use it before a release.
- `wp-performance`: profiling and fixes for HTTP calls, cron, autoloaded options and database queries.
- `wp-project-triage`: the repository detector the other skills run first.

When using them:

- The skills target WordPress 7.0+, but this plugin supports WordPress 5.0+ and PHP 7.4 (`Requires at least` and `Requires PHP` in `readme.txt`). Before using an API, check the version that introduced it and add a fallback, as `scrub_utf8()` does for `wp_scrub_utf8()`.
- Their commands use paths like `node skills/<name>/scripts/...`. Here the scripts are in `.claude/skills/<name>/scripts/`.
- The local WordPress in Docker has no WP-CLI, so steps that need `wp` (doctor, profile, Plugin Check) cannot run. Say so instead of reporting them as done.
- `.claude/` is outside the build allowlist, so the skills never ship with the plugin.

To update, copy the skill folders again from upstream `skills/` (or run `npx skills add WordPress/agent-skills --skill <name>` with the project scope), read the diff before committing, since the skills become instructions for Claude, and update the commit above.

## Releasing a version

The version number lives in four places, and all of them change together:

- `cf7-to-zapier.php`: the `Version:` header and the `CFTZ_VERSION` constant.
- `readme.txt`: `Stable tag`, a new entry at the top of `== Changelog ==` and, when users need a reason to update, one in `== Upgrade Notice ==`.

Releases always come from `master`, after the release changes are merged and pushed. Run `./ci/build.sh` (it takes no arguments). It builds `master` into `ci/build/` (ignored by git):

- `cf7-to-zapier/`: the plugin files.
- `cf7-to-zapier-<version>.zip`: the same files, installable from the WordPress admin.

The build stops when local `master` differs from `origin/master` or when the four version places disagree, and it needs a changelog entry for the version. It packs committed files only, from an allowlist (`FILES`); when the plugin gets a new top-level file or folder, add it there.

With `SVN_DIR` set (environment variable or `ci/.env`, see `ci/.env.example`) pointing to the local checkout of `https://plugins.svn.wordpress.org/cf7-to-zapier`, the script then releases to SVN:

1. Runs `svn update` and asks before going on if the checkout already has uncommitted changes.
2. Asks before going on if the version is not higher than the one in `trunk`, or if `tags/<version>` already exists.
3. Replaces the contents of `trunk/` and `tags/<version>/` with the build, schedules removed files for deletion (`svn rm`) and runs `svn add * --force`.
4. Shows `svn status` and asks before running `svn ci -m "v<version>"`.

Never run the SVN commit for the maintainer: the script asks them, and the commit publishes the release to every site.

## Website (cf7-to-webhook.valney.dev)

`https://cf7-to-webhook.valney.dev` is a static site served by nginx, built from `ci/website`. It hosts the plugin documentation and the webhook templates. It replaced GitHub Pages and the GitHub wiki, so new docs go here, never to the wiki.

### Layout

| Path | What it is |
|---|---|
| `ci/website/Dockerfile` | `nginx-unprivileged` image, port 8080. The build context is the **repository root**, so `COPY` paths start with `ci/website/`. |
| `ci/website/nginx/default.conf` | Routing, server-side includes, the CORS header for `templates.json`, the 404 page and `/healthz`. |
| `ci/website/public/` | The site root. Every file here is published as is. |
| `public/index.html` | Home: what the plugin does, how to start, and the Download, docs and GitHub links. |
| `public/partials/` | `header.html` (menu with Download and Donate), `footer.html` and `docs-nav.html` (the docs menu), shared by every page. |
| `public/site.js` | Marks the current page in the menus with `aria-current`, and turns docs headings with an `id` into links that copy the section address. |
| `public/faq.html` | The FAQ from `readme.txt`, served at `/faq`. |
| `public/security.html` | How to report a vulnerability (GitHub private reporting) and the acknowledgments, served at `/security`. When a fix credits a reporter in the `readme.txt` changelog, add them here too, newest first. |
| `public/404.html` | Served for any address that does not exist. |
| `public/style.css` | The only stylesheet, shared by every page, with light and dark colors as CSS variables. |

Cloudflare keeps `style.css` and `site.js` in browsers for 4 hours. When either changes, raise the `?v=` number where pages load them: the `<link>` in every page and the `<script>` in `partials/footer.html`.
| `public/docs/index.html` | The docs index, grouped by topic (Get started, Form data, Requests, Files, Errors, Templates), with a card per page. |
| `public/docs/how-to/<slug>.html` | Guides for a task, served at `/docs/how-to/<slug>`. |
| `public/docs/features/<slug>.html` | One page per plugin feature, served at `/docs/features/<slug>`. |
| `public/templates.json` | The webhook templates loaded by the plugin. |

Routing: `/<path>` serves `<path>`, then `<path>.html`, then `<path>/index.html`, and otherwise `404.html` with status 404. A trailing slash redirects to the address without it (`/docs/` to `/docs`). Link to pages without `.html` and without a trailing slash.

The URL sections (`how-to`, `features`) say what kind of page it is; the menu and the index group pages by topic instead. Sections have no page of their own: nginx sends `/docs/how-to` and `/docs/features` to `/docs`.

Pages include the shared parts with nginx server-side includes: `<!--# include virtual="/partials/header.html" -->`, `footer.html` and, on docs pages, `docs-nav.html`. Edit a menu or the footer once, in `partials/`, and every page gets it. `/partials/` is internal, so those files are not pages of their own. Includes only run in nginx: preview the site with the Docker image, not by opening the files.

`faq.html` is the full FAQ. `readme.txt` keeps a shorter one, with the general questions and a last "More questions?" entry linking to the site FAQ. When a question in the readme changes, change it in `faq.html` too, with links to this site as relative paths (`/docs/...`). Specific questions go only to the site, usually with a how-to.

Site content is in English, like the plugin.

### templates.json is load-bearing

Every installed copy of the plugin loads `https://cf7-to-webhook.valney.dev/templates.json` from the WordPress admin, with a cross-origin `$.getJSON` ([class-module-cf7.php](modules/cf7/class-module-cf7.php), [admin.js](modules/cf7/admin/assets/admin.js)). Every released version has that URL hardcoded, so this contract is permanent:

- The file stays at that exact URL.
- It stays valid JSON. Check with `python3 -m json.tool ci/website/public/templates.json`.
- nginx keeps `Access-Control-Allow-Origin: *` on it (the `location = /templates.json` block). Without it the browser blocks the response and the template selector breaks on every site.

With `WP_DEBUG` on (`ctz_is_developing()`) in a repository checkout, the plugin loads `ci/website/public/templates.json` straight from the plugin folder, so template changes can be tested before deploying the site. Released copies do not ship `ci/`, so they always load the published file.

### Adding a doc page

1. Pick the section: `how-to` for a guide to a task, `features` for what a feature does and how it behaves. Pick a slug in lowercase with hyphens. The page will live at `/docs/<section>/<slug>`.
2. Create `ci/website/public/docs/<section>/<slug>.html` by copying a page from the same section, such as `features/media-library.html`, and keep its structure: `<title>Page title | CF7 to Webhook</title>`, a meta description, a canonical URL with the full address, `<link rel="stylesheet" href="/style.css?v=N">` with the same `?v=` as the other pages, the header, docs menu and footer includes, and the breadcrumb to `/docs` followed by the section name.
3. Put styles in `style.css`, never inline. Use the existing variables so the page works in dark mode.
4. Give every `<h2>` an `id` with its text in lowercase and hyphens, unique on the page: `<h2 id="what-gets-stored">What gets stored</h2>`. Readers share sections as `/docs/<section>/<slug>#<id>`, so keep the `id` when you reword a heading.
5. Inside `<pre><code>`, escape `<`, `>` and `&` as `&lt;`, `&gt;` and `&amp;`. PHP's `->` and `=>` are the usual cases.
6. When the page tells the reader to add PHP code (filters, actions, snippets), link to `/docs/how-to/add-custom-code` there. The same goes for `readme.txt`, with the full URL.
7. Add the page to its topic in `partials/docs-nav.html` and as a card in the same topic of `docs/index.html`, with the page's meta description as the card text.
8. If the plugin should point to the page, link it from `readme.txt` (see "Never edit README.md") or from the webhook panel, where links open in a new tab (`target="_blank" rel="noopener"`).
9. Test locally (below) before pushing.

Write in English, in a technical register: the page states what happens and what the reader can do, with real paths, option names and code. A feature page says which version introduced it ("Since 5.2.0"). The site deploys from `master`, so a page about an unreleased feature goes live with the merge. When the maintainer's `escrita-sem-sotaque` skill is available, pass the prose through it.

Other pages work the same way: `public/<name>.html` is served at `/<name>`.

### Moving or renaming a doc page

Released readme files and plugin versions link to doc addresses and cannot be changed, so an old address must keep working:

1. Move the file with `git mv` and update its canonical URL.
2. Add a `301` from the old address (with and without `.html`) to the new one in `ci/website/nginx/default.conf`, next to the existing `make-multiple-webhooks-optionals` rule.
3. Update the links in `docs/index.html`, `readme.txt` and the plugin code.
4. Test that the old address answers `301` with the new `Location`.

### Testing locally

From the repository root:

```bash
docker build -f ci/website/Dockerfile -t cf7-to-webhook-website .
docker run --rm -p 8080:8080 cf7-to-webhook-website
```

Then check every page that changed, plus:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/docs/<section>/<slug>   # 200
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/nao-existe     # 404, never 200
curl -s -D - -o /dev/null http://localhost:8080/templates.json | grep -i access-control   # Access-Control-Allow-Origin: *
curl -s http://localhost:8080/healthz                                         # ok
```

### Deploy

Easypanel runs the site as the app service `cf7-to-webhook-valney-dev` in the project `sites`. It builds `ci/website/Dockerfile` from the repository root, the domain points to port 8080, and **auto-deploy is on for `master`**: every push to `master` publishes the site within a minute or two, whatever else the commit contains. Treat a push that touches `ci/website` as a production deploy.

After pushing, run the same checks against `https://cf7-to-webhook.valney.dev`.
