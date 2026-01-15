# dojob-2026

Local deployment of the Dojob project.

## Security
Do not commit secrets or credentials. Keep them in `.env` and local config only.

This repo includes a local `pre-push` hook that scans outgoing commits for
common secret patterns and blocks the push if it finds anything.
