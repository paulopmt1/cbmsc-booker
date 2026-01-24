#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== CBMSC Booker Production Deployment ===${NC}"

# Set default MySQL credentials
export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-root}"

# Check required environment variables
REQUIRED_VARS=("APP_SECRET" "GOOGLE_CREDENTIALS_JSON" "CPF_DO_QUERUBIN")
MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        MISSING_VARS+=("$var")
    fi
done

if [ ${#MISSING_VARS[@]} -ne 0 ]; then
    echo -e "${RED}Error: Missing required environment variables:${NC}"
    for var in "${MISSING_VARS[@]}"; do
        echo -e "  - $var"
    done
    echo ""
    echo "Este é o padrão de variáveis de ambiente para o deploy:"
    echo ""
    echo "  export APP_SECRET=\"\$(openssl rand -hex 32)\""
    echo "  export GOOGLE_CREDENTIALS_JSON=\"'{"type":"service_account","project_id":"cbmsc-book...\"
    echo "  export CPF_DO_QUERUBIN=\"000000000\" # (somente números do CPF do Querubin)"
    exit 1
fi

echo -e "${YELLOW}Step 1/4: Building production Docker image...${NC}"
docker compose -f docker-compose.prod.yml build --no-cache

echo -e "${YELLOW}Step 2/4: Stopping existing containers (if any)...${NC}"
docker compose -f docker-compose.prod.yml down 2>/dev/null || true

echo -e "${YELLOW}Step 3/4: Starting production containers...${NC}"
docker compose -f docker-compose.prod.yml up -d

echo -e "${YELLOW}Step 4/4: Waiting for services to be healthy...${NC}"
sleep 10

# Check if containers are running
if docker compose -f docker-compose.prod.yml ps | grep -q "Up"; then
    echo -e "${GREEN}=== Deployment Complete ===${NC}"
    echo ""
    echo "Application is running at: http://localhost:5001"
    echo ""
    echo "To view logs:"
    echo "  docker compose -f docker-compose.prod.yml logs -f"
    echo ""
    echo "To stop the application:"
    echo "  docker compose -f docker-compose.prod.yml down"
else
    echo -e "${RED}Error: Containers failed to start. Check logs:${NC}"
    docker compose -f docker-compose.prod.yml logs
    exit 1
fi
