# azguard-release

Use when preparing a release: version bump, changelog, GitHub release, or tag.

## Release Workflow

1. Ensure `main` branch is clean and CI passes.
2. Choose version following [Semantic Versioning](https://semver.org):
   - `patch` — backward-compatible bug fixes
   - `minor` — new backward-compatible features
   - `major` — breaking changes
3. Create and push a version tag: `git tag v1.2.3 && git push origin v1.2.3`
4. GitHub Actions `release.yml` and `changelog.yml` run automatically:
   - `changelog.yml` — updates `CHANGELOG.md` via git-cliff
   - `release.yml` — creates the GitHub Release with generated notes
5. Verify the release at GitHub Releases page.

## Relevant Workflows

- `.github/workflows/release.yml` — creates GitHub Release on tag push
- `.github/workflows/changelog.yml` — updates CHANGELOG.md on tag push
- `.github/workflows/release-drafter.yml` — drafts upcoming release notes

## Commit Messages

All commits on `main` must use Conventional Commits format (enforced by `pr-check.yml`). The PR title is used as the squash-merge commit message.

## Do Not

- Manually edit `CHANGELOG.md` — git-cliff owns it.
- Tag a release without passing CI.
- Force-push to `main`.
