# Publishing the GitHub Wiki

The GitHub wiki for this repository is stored in a separate Git repository:

`https://github.com/Greazi-Times/Bedrijvendag.wiki.git`

This environment could not push directly because the wiki remote requested GitHub credentials.

## Publish steps

From any machine with GitHub access to the repository:

```bash
git clone https://github.com/Greazi-Times/Bedrijvendag.wiki.git Bedrijvendag.wiki
cd Bedrijvendag.wiki
cp ../Bedrijvendag2/docs/wiki/*.md .
cp -R ../Bedrijvendag2/docs/wiki/screenshots .
git add .
git commit -m "Add dashboard documentation"
git push origin master
```

If the wiki repository does not exist yet, enable Wikis in the GitHub repository settings first, then open the Wiki tab once to initialize it.

## Updating later

1. Update the source documentation in `docs/DASHBOARD_GUIDE.md`.
2. Update the matching wiki pages in `docs/wiki/`.
3. Capture new screenshots in `docs/screenshots/`.
4. Copy the wiki pages and screenshots into the wiki clone.
5. Commit and push the wiki repository.
