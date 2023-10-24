test:
	@ composer exec --verbose phpunit tests

check-style:
	@ composer exec phpcs

fix-style:
	@ composer exec phpcbf

analyze:
	@ composer exec phpstan analyse src tests

all: check-style analyze test