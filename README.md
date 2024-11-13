# ELK Stack Project - Multi-Server Logging Setup

This project demonstrates a complete logging setup using the ELK Stack (ElasticSearch, Logstash, and Kibana) across multiple server types. By configuring different servers (Windows, Linux, and application servers) to send logs to a central ELK stack, we achieve centralized monitoring, improved visibility, and advanced alerting. Each server configuration has its own setup guide, and Kibana is configured for custom alerts and visualization.

## Table of Contents

- [ELK Stack Setup](./elk-setup.md): Configuration and setup of the main ELK Stack on Docker, which includes ElasticSearch, Logstash, and Kibana.
- [Windows Server Configuration](./windows-server.md): Instructions for setting up Winlogbeat on a Windows server to forward event logs to the ELK stack.
- [Ubuntu Server Configuration with Apache & MySQL](./ubuntu-server.md): Setup of Filebeat and rsyslog on an Ubuntu server to capture and send Apache and MySQL logs.
- [Kibana Alerts and Visualizations](./kibana-alerts.md): Guide on configuring Kibana for creating custom alerts, dashboards, and visualizations for monitoring various server activities.

## Project Overview

This ELK stack setup enables centralized logging from multiple types of servers, helping with log aggregation, analysis, and alert generation. Each server type has a tailored configuration guide in this repository, which allows different log types (e.g., Windows event logs, Apache logs, MySQL logs) to be collected, analyzed, and visualized in Kibana.

With this setup, you will be able to:
1. Collect logs from diverse sources using Filebeat, Winlogbeat, and rsyslog.
2. Configure custom parsing and filtering rules in Logstash.
3. Visualize data in Kibana with dashboards and generate alerts for specific events.

Each section below links to a dedicated `.md` file that provides a detailed step-by-step setup guide for each server configuration.
