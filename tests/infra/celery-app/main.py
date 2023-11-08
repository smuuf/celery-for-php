import time
import celery
from os import environ
from typing import Union


broker_uri = environ.get('c4p_tests_py_broker_hostname')
backend_uri = environ.get('c4p_tests_py_backend_hostname')
# broker_uri = 'pyamqp://guest@[::1]:40002'
# backend_uri = 'redis://[::1]:40001'


class Config:
    task_serializer = 'json'


number = Union[int, float]
app = celery.Celery('tasks', backend=backend_uri, broker=broker_uri,
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

# worker = app.Worker()
# worker.start()
