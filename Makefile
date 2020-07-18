default:

phpstan:
	phpstan analyse --level 8 src/

rector:
	docker run --rm \
		-v $(shell pwd):/project \
		rector/rector:latest process /project/src \
		--config /project/rector.yaml \
		--autoload-file /project/vendor/autoload.php
