FROM debian:bookworm-slim

COPY . /var/klodworld
RUN chmod +x /var/klodworld/setup/install.sh

WORKDIR /var/klodworld/setup

EXPOSE 443
EXPOSE 8080

CMD ["/bin/bash", "-c", "./install.sh; exec bash"]