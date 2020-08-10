code-quality: code-phpstan code-rector

code-phpstan:
	phpstan analyse --level max src/

code-rector:
	rector process src/
