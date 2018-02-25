#! /bin/bash
cp ./pre-commit ../.git/hooks/pre-commit
cp ./pre-push ../.git/hooks/pre-push
chmod +x ../.git/hooks/pre-commit
chmod +x ../.git/hooks/pre-push