# Donation Manager

WordPress plugin (`smcothrift/donation-manager`) powering the donation intake form on
PickUpMyDonation.com and SMCoThrift's ecosystem. Distributed via Composer from
`git@github.com:SMCoThrift/donation-manager.git`.

## Local dev setup

This directory (`localdev-donation-manager`) is the actual development copy — its own
git repo, where real commits belong. A sibling plugin folder in the same Bedrock site,
`donation-manager`, is the VCS/composer-managed copy pulled from this repo's tagged
releases; it's gitignored in the site's own repo and gets overwritten on `composer
update`. Never hand-edit `donation-manager` directly — changes there are invisible to
git and silently lost.

A custom `localdev-switcher` plugin toggles which of the two is WP-active. Before editing
or testing anything, confirm which copy is actually active — it changes independently of
this conversation, mid-workflow, whenever the toggle is clicked in wp-admin:

```bash
cd /Users/mwender/webdev/laravel-valet/bedrock/pickupmydonation.com/web
wp plugin list --status=active | grep donation
# or, authoritative, bypasses any wp-cli display staleness:
wp option get active_plugins --format=json | grep donation
```

If testing needs to happen while logged out of wp-admin (session/session-cookie related
work) and the toggle can't be clicked in the browser, it can be replicated directly —
this is exactly what the plugin's own toggle link does:

```bash
wp option update localdev_switcher_overrides '{"plugins":["donation-manager"],"themes":[]}' --format=json
wp eval '
$active = get_option("active_plugins", []);
$active = array_map(function($p) {
  return $p === "donation-manager/donation-manager.php" ? "localdev-donation-manager/donation-manager.php" : $p;
}, $active);
update_option("active_plugins", $active);
'
```
(Reverse the `array_map` condition, and drop `"donation-manager"` from the overrides
array, to switch back to VCS.)

## Releasing a new version

Work incrementally — land each code change as its own focused commit as you go, so by
the time you're ready to release, the working tree is already clean and every intended
change is committed. Then:

1. **Decide the next version number** — semver (`MAJOR.MINOR.PATCH`), based on the
   nature of the changes and existing tags (`git tag`).
2. **Document the release** — update all three together:
   - `readme.txt`: add a new `= X.Y.Z =` entry at the top of `== Changelog ==`
     summarizing the changes as a bullet list, **and** bump `Stable tag:` in the file's
     header to match.
   - `donation-manager.php`: bump `Version:` in the plugin header docblock to match.
   - Run `composer readme` to regenerate `README.md` from `readme.txt` (via `wp2md`).
3. **Commit the documentation update** as a single commit: `Documenting vX.Y.Z`.
4. **Tag the release** — lightweight tag, plain version number, no `v` prefix, applied to
   the documentation commit: `git tag X.Y.Z`.
5. **Push commits and the tag** — `composer update` in the Bedrock project can't see the
   release until this happens:
   ```bash
   git push && git push origin X.Y.Z
   ```

### After releasing

Pull the new version into the Bedrock project and test it as the actual VCS copy before
considering it fully verified:

```bash
cd /Users/mwender/webdev/laravel-valet/bedrock/pickupmydonation.com
composer update smcothrift/donation-manager
```

Then switch the site to the `donation-manager` (VCS) copy via the `localdev-switcher`
toggle in wp-admin — or the WP-CLI equivalent above, reversed.
