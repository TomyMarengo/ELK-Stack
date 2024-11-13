# ELK Stack Setup Guide

This guide explains how to set up an ELK stack on a Docker environment to log data from various servers. This configuration includes ElasticSearch, Logstash, and Kibana.

## Machine 1: ELK Stack Setup

### Step 1: Set Up Working Directory

First, create a working directory for the ELK stack and navigate into it:

```bash
mkdir elk-stack
cd elk-stack
```

### Step 2: Create Docker Compose Configuration

Create a `docker-compose.yml` file in the `elk-stack` directory with the following content:

```yaml
version: '3.7'
services:
  elasticsearch:
    image: docker.elastic.co/elasticsearch/elasticsearch:7.17.10
    container_name: elasticsearch
    environment:
      - node.name=elasticsearch
      - discovery.type=single-node
      - bootstrap.memory_lock=true
      - "ES_JAVA_OPTS=-Xms1g -Xmx1g"
    ulimits:
      memlock:
        soft: -1
        hard: -1
    volumes:
      - esdata:/usr/share/elasticsearch/data
    ports:
      - 9200:9200
    networks:
      - elk

  logstash:
    image: docker.elastic.co/logstash/logstash:7.17.10
    container_name: logstash
    volumes:
      - ./logstash/pipeline:/usr/share/logstash/pipeline
    ports:
      - 5044:5044
      - 5000:5000
      - 9600:9600
    networks:
      - elk
    depends_on:
      - elasticsearch

  kibana:
    image: docker.elastic.co/kibana/kibana:7.17.10
    container_name: kibana
    environment:
      - ELASTICSEARCH_HOSTS=http://elasticsearch:9200
      - XPACK_ENCRYPTEDSAVEDOBJECTS_ENCRYPTIONKEY=MiClaveDeEncriptacionSegura12345678901234
    ports:
      - 5601:5601
    networks:
      - elk
    depends_on:
      - elasticsearch

volumes:
  esdata:

networks:
  elk:
    driver: bridge
```

### Step 3: Configure Logstash

Create a directory for Logstash configuration files:

```bash
mkdir -p logstash/pipeline
```

Inside the `logstash/pipeline` directory, create a `logstash.conf` file with the following configuration:

```plaintext
input {
  beats {
    port => 5044
  }
  syslog {
    port => 5000
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
  }
}
```

- beats: Configures Logstash to receive data from Beats agents (e.g., Filebeat, Winlogbeat) on port `5044`.
- syslog: Configures Logstash to receive logs sent over the `syslog` protocol on port `5000`.


### Step 4: Start the ELK Stack

Start up the ELK stack using Docker Compose in detached mode:

```bash
sudo docker-compose up -d
```

### Step 5: Verify ELK Stack Containers

Check that the containers are running successfully:

```bash
sudo docker ps -a
```

To view the logs for the entire stack, use:

```bash
sudo docker-compose logs
```

### Troubleshooting

#### Access Kibana

Once the stack is up, Kibana should be accessible at `http://localhost:5601`. If you cannot access Kibana:

1. Verify the container is running:

```bash
sudo docker ps | grep kibana
```

2. Check Kibana logs for errors:

```bash
sudo docker-compose logs kibana
```

#### Verify ElasticSearch Status

ElasticSearch should be available at `http://localhost:9200`. To test if it is running correctly:

1. Run a `curl` command:

```bash
curl -X GET "localhost:9200/"
```

This should return JSON with cluster information.

2. If ElasticSearch is unresponsive:

- Verify the ElasticSearch container:

```bash
sudo docker-compose logs elasticsearch
```

- Check memory allocation settings in docker-compose.yml to ensure sufficient resources.

#### Common Issues

- Port Conflicts: Ensure no other services are using ports `9200`, `5044`, `5000`, or `5601`.
- Memory Limitations: ElasticSearch requires substantial memory. If it fails to start, consider increasing Docker memory allocation.

This concludes the setup guide for the ELK stack on the primary machine. Repeat similar steps for setting up agents on other machines, and configure Filebeat or Winlogbeat for each to send logs to this central ELK stack.
