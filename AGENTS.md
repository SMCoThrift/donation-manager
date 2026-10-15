# Donation Manager

WordPress plugin (`smcothrift/donation-manager`) powering the donation intake form on PickUpMyDonation.com and SMCoThrift's ecosystem. Distributed via Composer from `git@github.com:SMCoThrift/donation-manager.git`.

## Local dev setup

This directory (`localdev-donation-manager`) is the actual development copy — its own git repo, where real commits belong. A sibling plugin folder in the same Bedrock site, `donation-manager`, is the VCS/composer-managed copy pulled from this repo's tagged releases; it's gitignored in the site's own repo and gets overwritten on `composer update`. Never hand-edit `donation-manager` directly — changes there are invisible to git and silently lost.

A custom `localdev-switcher` plugin toggles which of the two is WP-active. Before editing or testing anything, confirm which copy is actually active — it changes independently of this conversation, mid-workflow, whenever the toggle is clicked in wp-admin:

```bash
cd /Users/mwender/webdev/laravel-valet/bedrock/pickupmydonation.com/web
wp plugin list --status=active | grep donation
# or, authoritative, bypasses any wp-cli display staleness:
wp option get active_plugins --format=json | grep donation
```

If testing needs to happen while logged out of wp-admin (session/session-cookie related work) and the toggle can't be clicked in the browser, it can be replicated directly — this is exactly what the plugin's own toggle link does:

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
(Reverse the `array_map` condition, and drop `"donation-manager"` from the overrides array, to switch back to VCS.)

## Releasing a new version

`main` is protected: every change reaches it through a pull request (0 approvals required, since the maintainer can't approve their own PRs). Don't push to `main` directly, not even the release docs commit. GitHub lets the owner's account bypass the rule, but the point is to keep every change in a PR.

1. **Branch and commit as you go.** Work on a feature branch and land each code change as its own focused commit, so the working tree is clean by release time.
2. **Decide the next version number.** Use semver (`MAJOR.MINOR.PATCH`) based on the changes and the existing tags (`git tag`).
3. **Document the release on the same branch.** Update all three together:
   - `readme.txt`: add a new `= X.Y.Z =` entry at the top of `== Changelog ==` summarizing the changes as a bullet list, **and** bump `Stable tag:` in the file's header to match.
   - `donation-manager.php`: bump `Version:` in the plugin header docblock to match.
   - Run `composer readme` to regenerate `README.md` from `readme.txt` (via `wp2md`).
4. **Commit the documentation update** as a single commit, `Documenting vX.Y.Z`, as the last commit on the branch.
5. **Open the PR and merge it.** Use `git push -u origin <branch>`, then `gh pr create`. Merge after review (in the GitHub UI or with `gh pr merge`).
6. **Tag the merge commit on `main`.** Use a lightweight tag with the plain version number and no `v` prefix. `composer update` in the Bedrock project can't see the release until the tag is pushed:
   ```bash
   git checkout main && git pull
   git tag X.Y.Z && git push origin X.Y.Z
   ```

### After releasing

The Bedrock project pins an exact version. Bump `"smcothrift/donation-manager"` in its `composer.json` to `X.Y.Z`, then pull the release in:

```bash
cd /Users/mwender/webdev/laravel-valet/bedrock/pickupmydonation.com
composer update smcothrift/donation-manager
```

Test it as the actual VCS copy before considering it verified: switch the site to the `donation-manager` (VCS) copy with the `localdev-switcher` toggle in wp-admin, or with the WP-CLI equivalent above, reversed. Then commit `Updating Donation Manager (A.B.C => X.Y.Z)` in the Bedrock repo. Pushing Bedrock `main` deploys to production, so confirm before pushing unless a deploy was asked for.
