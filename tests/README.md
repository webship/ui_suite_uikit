# UI Suite UIkit tests

The behavior tests of the UI Suite UIkit theme: Gherkin features run by
[webship-js](https://www.npmjs.com/package/webship-js) (Playwright and Cucumber)
with the Drupal steps of
[@webship-js/drupal-core-steps](https://www.npmjs.com/package/@webship-js/drupal-core-steps)
(the login by account handle). `cucumber.js` in the project root holds the
configuration. The features check the UIkit markup the theme renders, not the
content of a site: the front page, the offcanvas menu, the forms and local
tasks, the color modes, the sign-in screens, the HTMX navigation, the fonts,
the theme settings, UI Skins, the component library, Display Builder and the
accessibility of the pages (WCAG 2.2 AA and AAA).

| Folder | What it holds |
| --- | --- |
| `features/` | the features (17 files, 129 scenarios) |
| `step-definitions/` | this project's own steps; the fixture steps change the site with Drush and put it back |
| `fixtures/` | a page layout the Display Builder feature creates and deletes |
| `reports/`, `screenshots/`, `videos/` | what a run writes (not committed) |

## From nothing to a green run

### 1. A site of the Website project template in DDEV

The [Website](https://www.drupal.org/project/website) project template installs the Webship
site templates with UI Suite UIkit, UIkit Admin and Display Builder:

```bash
mkdir website && cd website
ddev config --project-type=drupal11 --docroot=web --php-version=8.4
ddev start
ddev composer create-project drupal/website:1.0.x-dev
ddev restart
```

### 2. The site, with the test accounts

The suite logs in as the accounts that `cucumber.js` lists in
`worldParameters.users`, and never creates or deletes an account: the site
build does. Choose two passwords, keep them in your environment, install the
site with Website Starter and the Webmaster account, and add the account whose
failed logins the sign-in feature counts:

```bash
export DRUPAL_ADMIN_PASS='…' DRUPAL_AUTHENTICATED_PASS='…'
ddev drush site:install ../recipes/website_starter -y --account-name=webmaster --account-mail=webmaster@example.test --account-pass="$DRUPAL_ADMIN_PASS" --site-name="Website"
ddev drush user:create authenticated_user --mail=authenticated_user@example.test --password="$DRUPAL_AUTHENTICATED_PASS"
curl -sk https://website.ddev.site/ | grep -oE '/sites/default/files/styles/[^" ,]+' | sed 's/&amp;/\&/g' | sort -u \
  | while read -r path; do curl -sk -o /dev/null "https://website.ddev.site$path"; done
```

The last command asks once for the images of the front page: a new site makes
each image style on its first request, and answers 503 to a request that comes
in while the same image is being made, which a scenario that counts the errors
of a page would see.

Any site with the theme as its default theme works the same way; the scenarios
that need a module the site does not have (Webform, Contact, Display Builder
and its page layouts, UI Patterns Library) skip themselves, and so do the
scenarios of the log in and password pages when another theme draws them: on a
Website site UIkit Admin draws the sign-in screens, and 21 scenarios skip
there. The sign-in features hand the screens to this theme for their own
scenarios. CI runs the suite on a Standard site with the theme as the default
theme, the same accounts with passwords made for the job: see the
`webship-js-test` job in `.gitlab-ci.yml`.

### 3. Run the suite

From this project's folder, with Node.js 24 and npm:

```bash
npm install
npx playwright install chromium
export LAUNCH_URL=https://website.ddev.site
export NODE_EXTRA_CA_CERTS="$(mkcert -CAROOT)/rootCA.pem"
export DRUSH="ddev drush" DRUPAL_PROJECT_DIR=~/website
npm test -- --tags "not @wip and not @theme-optional-config"
```

The custom steps change theme settings and put them back with Drush:
`DRUSH` is the command that runs Drush for the site, `DRUPAL_PROJECT_DIR` the
folder it runs in. The login form of a Webship site has a time limit (Honeypot,
2 seconds): the login by handle waits `DRUPAL_LOGIN_WAIT` milliseconds before it
submits, 3000 by default (`DRUPAL_LOGIN_WAIT=0` on a site without it).
`NODE_EXTRA_CA_CERTS` lets Node.js trust DDEV's HTTPS certificate for the steps
that send a request outside the browser.

`npm run test:dry` checks that every step resolves to one definition, without a browser.

The tag expression leaves out the scenarios that cannot pass on that site,
each for a reason its feature states:

- `@theme-optional-config`: a site template installs the theme from a recipe, without its optional configuration (the Display Builder profiles and the sign-in page layout); a site that installs the theme itself runs them (2 scenarios).

### 4. What green looks like

```
127 scenarios (22 skipped, 105 passed)
1054 steps (170 skipped, 884 passed)
```

The HTML report is written to `tests/reports/cucumber_report.html`. A failed
step leaves a screenshot in `tests/screenshots/` and a video in
`tests/videos/`. The fixture steps put back what they change (the theme settings,
UI Skins, the administration theme, the default page layout, the blocks they
place) and the contact scenario removes its submission, so a second run starts
from the same site and is green too.

## Test users

The accounts as `cucumber.js` lists them in `worldParameters.users`. Each
value comes from the environment variable named below; `CHANGE_ME` stands for
the password you chose in step 2.

```json
{
  "Webmaster": { "username": "webmaster", "password": "CHANGE_ME", "email": "webmaster@example.test", "isAdmin": true },
  "Authenticated user": { "username": "authenticated_user", "password": "CHANGE_ME", "email": "authenticated_user@example.test", "roles": [] }
}
```

| Handle | Variables | The account |
| --- | --- | --- |
| Webmaster | `DRUPAL_ADMIN_USER`, `DRUPAL_ADMIN_PASS`, `DRUPAL_ADMIN_MAIL` | the account the installer created |
| Authenticated user | `DRUPAL_AUTHENTICATED_USER`, `DRUPAL_AUTHENTICATED_PASS`, `DRUPAL_AUTHENTICATED_MAIL` | a user with no role; the sign-in feature locks it out with failed logins and clears them |
