FROM debian:bookworm-slim

COPY . /var/klodworld

WORKDIR /var/klodworld/setup

EXPOSE 443
EXPOSE 8080

CMD ["/bin/bash", "-c", "chmod +x install.sh; ./install.sh; exec bash"]