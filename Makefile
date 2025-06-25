build:
	docker build -t klodworld .
	docker run -p 2443:443 -p 2080:8080 -it --rm --name klodworld klodworld