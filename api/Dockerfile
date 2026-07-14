FROM python:3.12-slim AS builder

WORKDIR /app

RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    build-essential \
    gcc \
    libnetcdf-dev \
    libhdf5-dev && \
    rm -rf /var/lib/apt/lists/*

COPY . /app

RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Stage 2: Final, smaller image
FROM python:3.12-slim

WORKDIR /app

EXPOSE 5000

COPY --from=builder /app /app
COPY --from=builder /usr/local/lib/python3.12/site-packages /usr/local/lib/python3.12/site-packages
COPY --from=builder /usr/local/bin /usr/local/bin

CMD ["uvicorn", "radialdata:app", "--host", "0.0.0.0", "--port", "5000", "--workers", "2", "--timeout-keep-alive", "0"]
