# Copyright (c) 2026 xNetVN Inc.
# Website: https://xnetvn.com/
# License: Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
# Contact: license@xnetvn.net

FROM mcr.microsoft.com/devcontainers/universal:noble

RUN rm -f /etc/apt/sources.list.d/yarn.list \
	&& rm -f /usr/share/keyrings/yarnkey.gpg

RUN if ! id -u vscode >/dev/null 2>&1; then \
		useradd -m -s /bin/bash vscode; \
	fi

RUN if command -v sudo >/dev/null 2>&1; then \
		echo "vscode ALL=(root) NOPASSWD:ALL" > /etc/sudoers.d/vscode \
		&& chmod 0440 /etc/sudoers.d/vscode; \
	fi
