#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Stopping Sembot Helpdesk Lite (DEV MODE)${NC}"
echo -e "${YELLOW}========================================${NC}"

# Stop Angular Frontend (Dev Mode)
echo -e "\n${YELLOW}Stopping Angular Frontend (Dev)...${NC}"
cd frontend || exit 1
docker-compose --profile dev down
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Angular Frontend (Dev) stopped${NC}"
else
    echo -e "${RED}✗ Failed to stop Angular Frontend (Dev)${NC}"
fi

# Stop Laravel Sail (API)
echo -e "\n${YELLOW}Stopping Laravel API (Sail)...${NC}"
cd ../api || exit 1
./vendor/bin/sail down
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Laravel API stopped${NC}"
else
    echo -e "${RED}✗ Failed to stop Laravel API${NC}"
fi

cd ..

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ All services stopped successfully!${NC}"
echo -e "${GREEN}========================================${NC}"
