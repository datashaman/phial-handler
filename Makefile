clean-code: phpstan rector

phpstan:
	phpstan analyse --level max src/

rector:
	rector process src/
