ifeq ($(OS),Windows_NT)
	LOCAL_PATH := $(shell pwd -W)
else
	LOCAL_PATH := $(shell pwd)
endif

build:
	docker build -t klodworld .
	docker run -p 2443:443 -p 2080:8080 -it --rm --name klodworld klodworld
		-v "$(LOCAL_PATH):/var/klodworld" \
		-it --rm --name klodworld klodworld