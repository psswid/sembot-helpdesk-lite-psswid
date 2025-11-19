#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Restarting Sembot Helpdesk Lite${NC}"
echo -e "${YELLOW}========================================${NC}"

# Stop all services
./stop.sh

echo -e "\n${YELLOW}Waiting 3 seconds before restart...${NC}"
sleep 3

# Start all services
./start.sh
