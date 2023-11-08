import itertools
import threading
import subprocess
from os import environ
from types import SimpleNamespace


REDIS_DRIVER = 'PredisRedisDriver'
AMQP_DRIVER = 'PhpAmqpLibAmqpDriver'


# Workaround for "match" statement down below.
# See https://stackoverflow.com/a/67181772/1285669
drivers = SimpleNamespace()
drivers.REDIS_DRIVER = REDIS_DRIVER
drivers.AMQP_DRIVER = AMQP_DRIVER


BROKERS = [
    REDIS_DRIVER,
    AMQP_DRIVER,
]

BACKENDS = [
    REDIS_DRIVER,
]


def _build_hostname(driver_name: str) -> str:
    """Build final driver (e.g. redis connection, amqp connection, etc.) URI,
    depending on whether tests are being run inside Docker (primary case) or
    outside of Docker (can be handy when debugging).
    """
    match driver_name:
        case drivers.REDIS_DRIVER:
            if environ.get('DOCKER_TESTS'):
                return 'redis://redis-server'
            else:
                return 'redis://[::1]'

        case drivers.AMQP_DRIVER:
            if environ.get('DOCKER_TESTS'):
                return 'pyamqp://guest@rabbitmq/'
            else:
                return 'pyamqp://guest@[::1]'


processes: list[subprocess.Popen] = []


def _build_queue_name(broker: str, backend: str) -> str:
    """Because we have tests of many combinations of brokers vs result
    backends running simultaneously, we need to namespace our test tasks a bit.

    Consider, for example, testing "Redis broker + Redis result backend" as
    case A and at the same time having a different test testing "Redis broker
    + AMQP result backend" as case B. If a test in case A would post a Celery
    task into Redis, but then Celery running for case B (which uses the same
    broker) would get to the task first, then case A wouldn't receive the
    expected result in case A's redis result backend, because case B's Celery
    would execute the task and then store its result in case B's AMQP result
    backend.

    This name has to be constructed the same way the Celerys created and
    running during tests are constructing it.

    See `buildTestQueueName` in tests/bootstrap.php.
    """
    return f"test-queue-{broker}::{backend}"


def _run_celery_main(broker: str, backend: str) -> None:
    queue_name = _build_queue_name(broker, backend)

    env = {
        'c4p_tests_py_broker_hostname': _build_hostname(broker),
        'c4p_tests_py_backend_hostname': _build_hostname(backend),
    }

    command = f'python -m celery -A main worker -c 4 --loglevel=INFO -Q {queue_name}'

    p = subprocess.Popen(command.split(), env=environ | env)
    processes.append(p)
    p.wait()

# We'll create subprocesses running a standalone Celery for each possible
# broker and result backend combination.
# Because it's easier to "wait()" for several subprocesses to exit/terminate
# if we run each Celery process inside a separate thread, we'll do just that.
threads = [
    threading.Thread(target=_run_celery_main, args=(broker, backend))
    for broker, backend
    in itertools.product(BROKERS, BACKENDS)]

try:
    # Start all threads - each of them will start their own Celery process.
    [p.start() for p in threads]
    # Wait for all threads to finish - which will happen if their Celery process
    # finishes (gracefully or violently, we don't care).
    [p.join() for p in threads]
except BaseException:
    # Catch CTRL+C and stuff.
    pass

# Make sure all Celery subprocesses are terminated even if they did not
# exit on their own (e.g. if CTRL+C was caught above).
[p.terminate() for p in processes]
