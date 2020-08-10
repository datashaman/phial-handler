SOURCES = src/

code-quality: code-phpstan code-rector code-phpcs

code-phpstan:
	phpstan analyse --level max $(SOURCES)

code-rector:
	rector process $(SOURCES)

code-phpcs:
	phpcs $(SOURCES)

code-phpcbf:
	phpcbf $(SOURCES)
