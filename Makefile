test:
	@ composer exec --verbose phpunit tests

check-style:
	@ composer exec phpcs

fix-style:
	@ composer exec phpcbf

analyze:
	@ composer exec phpstan analyse

all: check-style analyze test