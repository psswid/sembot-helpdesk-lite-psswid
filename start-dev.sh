#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Starting Sembot Helpdesk Lite (DEV MODE)${NC}"
echo -e "${GREEN}========================================${NC}"

# Create shared network if it doesn't exist
echo -e "\n${YELLOW}Creating shared Docker network...${NC}"
if docker network inspect sembot-network >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Network 'sembot-network' already exists${NC}"
else
    docker network create sembot-network
    echo -e "${GREEN}✓ Network 'sembot-network' created${NC}"
fi

# Start Laravel Sail (API)
echo -e "\n${YELLOW}Starting Laravel API (Sail)...${NC}"
cd api || exit 1
./vendor/bin/sail up -d
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Laravel API started successfully${NC}"
else
    echo -e "${RED}✗ Failed to start Laravel API${NC}"
    exit 1
fi

# Start Angular Frontend in Development Mode
echo -e "\n${YELLOW}Starting Angular Frontend (Development Mode with Hot Reload)...${NC}"
cd ../frontend || exit 1
docker-compose --profile dev up -d frontend-dev
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Angular Frontend (Dev) started successfully${NC}"
else
    echo -e "${RED}✗ Failed to start Angular Frontend (Dev)${NC}"
    exit 1
fi

cd ..

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ All services started in DEV mode!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "\n${YELLOW}Services:${NC}"
echo -e "  • API:      http://localhost:80"
echo -e "  • Frontend: http://localhost:4200 (with hot reload)"
echo -e "\n${YELLOW}To view logs:${NC}"
echo -e "  • API:      cd api && ./vendor/bin/sail logs -f"
echo -e "  • Frontend: cd frontend && docker-compose logs -f frontend-dev"
echo -e "\n${YELLOW}To stop all services:${NC}"
echo -e "  • Run: ./stop-dev.sh"
echo -e "${GREEN}========================================${NC}"
