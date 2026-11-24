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

## Releasing a version

The version number lives in four places, and all of them change together:

- `cf7-to-zapier.php`: the `Version:` header and the `CFTZ_VERSION` constant.
- `readme.txt`: `Stable tag`, a new entry at the top of `== Changelog ==` and, when users need a reason to update, one in `== Upgrade Notice ==`.

## Website (cf7-to-webhook.valney.dev)

`https://cf7-to-webhook.valney.dev` is a static site served by nginx, built from `ci/website`. It hosts the plugin documentation and the webhook templates. It replaced GitHub Pages and the GitHub wiki, so new docs go here, never to the wiki.

### Layout

| Path | What it is |
|---|---|
| `ci/website/Dockerfile` | `nginx-unprivileged` image, port 8080. The build context is the **repository root**, so `COPY` paths start with `ci/website/`. |
| `ci/website/nginx/default.conf` | Routing, the CORS header for `templates.json`, the 404 page and `/healthz`. |
| `ci/website/public/` | The site root. Every file here is published as is. |
| `public/index.html` | Home: links to the repository and to the docs. |
| `public/404.html` | Served for any address that does not exist. |
| `public/style.css` | The only stylesheet, shared by every page, with light and dark colors as CSS variables. |
| `public/docs/index.html` | The list of doc pages. |
| `public/docs/<slug>.html` | One doc page each, served at `/docs/<slug>`. |
| `public/templates.json` | The webhook templates loaded by the plugin. |

Routing: `/<path>` serves `<path>`, then `<path>.html`, then `<path>/index.html`, and otherwise `404.html` with status 404. A trailing slash redirects to the address without it (`/docs/` to `/docs`). Link to pages without `.html` and without a trailing slash.

Site content is in English, like the plugin.

### templates.json is load-bearing

Every installed copy of the plugin loads `https://cf7-to-webhook.valney.dev/templates.json` from the WordPress admin, with a cross-origin `$.getJSON` ([class-module-cf7.php](modules/cf7/class-module-cf7.php), [admin.js](modules/cf7/admin/assets/admin.js)). Every released version has that URL hardcoded, so this contract is permanent:

- The file stays at that exact URL.
- It stays valid JSON. Check with `python3 -m json.tool ci/website/public/templates.json`.
- nginx keeps `Access-Control-Allow-Origin: *` on it (the `location = /templates.json` block). Without it the browser blocks the response and the template selector breaks on every site.

With `WP_DEBUG` on (`ctz_is_developing()`), the plugin loads the file from `home_url( '/cf7-to-webhook-valney-dev/templates.json' )` instead, so a local WordPress can map that path to `ci/website/public`.

### Adding a doc page

1. Pick a slug in lowercase with hyphens. The page will live at `/docs/<slug>`.
2. Create `ci/website/public/docs/<slug>.html` by copying an existing doc page, such as `make-multiple-webhooks-optionals.html`, and keep its structure: `<title>Page title | CF7 to Webhook</title>`, a meta description, a canonical URL with the full address, `<link rel="stylesheet" href="/style.css">`, the breadcrumb back to `/` and `/docs`, and the support forum footer.
3. Put styles in `style.css`, never inline. Use the existing variables so the page works in dark mode.
4. Inside `<pre><code>`, escape `<`, `>` and `&` as `&lt;`, `&gt;` and `&amp;`. PHP's `->` is the usual case.
5. Add a link to the new page in `ci/website/public/docs/index.html`.
6. If the plugin FAQ should point to it, change the link in `readme.txt` (see "Never edit README.md").
7. Test locally (below) before pushing.

Other pages work the same way: `public/<name>.html` is served at `/<name>`.

### Testing locally

From the repository root:

```bash
docker build -f ci/website/Dockerfile -t cf7-to-webhook-website .
docker run --rm -p 8080:8080 cf7-to-webhook-website
```

Then check every page that changed, plus:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/docs/<slug>   # 200
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8080/nao-existe     # 404, never 200
curl -s -D - -o /dev/null http://localhost:8080/templates.json | grep -i access-control   # Access-Control-Allow-Origin: *
curl -s http://localhost:8080/healthz                                         # ok
```

### Deploy

Easypanel runs the site as the app service `cf7-to-webhook-valney-dev` in the project `sites`. It builds `ci/website/Dockerfile` from the repository root, the domain points to port 8080, and **auto-deploy is on for `master`**: every push to `master` publishes the site within a minute or two, whatever else the commit contains. Treat a push that touches `ci/website` as a production deploy.

After pushing, run the same checks against `https://cf7-to-webhook.valney.dev`.
