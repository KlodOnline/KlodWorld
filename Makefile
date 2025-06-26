build:
	docker build -t klodworld .
	docker run -p 2443:443 -p 2080:8080 -v ".:/var/klodworld" -it --rm --name klodworld klodworld
