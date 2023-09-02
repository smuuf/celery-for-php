from os import environ
import time
import datetime
import celery
from typing import Union


_IN_DOCKER = bool(environ.get('DOCKER_TESTS'))


if _IN_DOCKER:
    CONNECTION = 'redis://redis-server'
else:
    CONNECTION = 'redis://127.0.0.1'

BROKER = CONNECTION
#BROKER = 'amqp://myuser:mypassword@localhost:5672/myvhost'


class Config:
    task_serializer = 'json'
    #task_serializer = 'msgpack'


number = Union[int, float]
app = celery.Celery('tasks', backend=CONNECTION, broker=BROKER,
                    config_source=Config)


@app.task
def add(x: number, y:number) -> number:
    return x + y


@app.task
def sum_list(l: list):
    return sum(l)


@app.task
def zip_dicts(a: dict, b: dict):
    return list(zip(a, b))


@app.task
def just_wait(how_long: number, retval = None):
    time.sleep(how_long)
    return retval


@app.task(track_started=True)
def just_wait_track_started(*args, **kwargs):
    return just_wait(*args, **kwargs)


# eta = datetime.datetime.utcnow() + datetime.timedelta(minutes=5)
# r = just_wait.apply_async(args=[10], kwargs={'retval': True}, eta=None)
# r.revoke()
