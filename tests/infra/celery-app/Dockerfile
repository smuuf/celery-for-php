FROM python:3.11-alpine

RUN adduser my_user --disabled-password
USER my_user

RUN pip install --user celery "celery[redis,msgpack]"

RUN mkdir ~/celery-app
COPY main.py start.sh ~/celery-app/

WORKDIR ~/celery-app
ENTRYPOINT ["sh", "./start.sh"]
