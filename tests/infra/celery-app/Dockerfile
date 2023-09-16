FROM python:3.11-alpine

RUN adduser my_user --disabled-password
USER my_user

RUN pip install --user celery "celery[redis]"

RUN mkdir /home/my_user/celery-app
COPY main.py start.sh /home/my_user/celery-app/

WORKDIR /home/my_user/celery-app
ENTRYPOINT ["sh", "./start.sh"]
