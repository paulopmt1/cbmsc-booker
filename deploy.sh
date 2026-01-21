#!/bin/bash
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== CBMSC Booker Production Deployment ===${NC}"

# Check required environment variables
REQUIRED_VARS=("MYSQL_ROOT_PASSWORD" "MYSQL_PASSWORD" "APP_SECRET")
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
    echo "Please set these variables before running the deployment:"
    echo ""
    echo "  export MYSQL_ROOT_PASSWORD=\"your-secure-root-password\""
    echo "  export MYSQL_PASSWORD=\"your-secure-password\""
    echo "  export APP_SECRET=\"\$(openssl rand -hex 32)\""
    echo ""
    echo "Optional variables (with defaults):"
    echo "  export MYSQL_DATABASE=\"cbmsc_booker\""
    echo "  export MYSQL_USER=\"cbmsc_user\""
    echo "  export GOOGLE_CREDENTIALS_PATH=\"/opt/secrets/cbmsc-booker-credentials.json\""
    exit 1
fi

# Check if Google credentials file exists
CREDS_PATH="${GOOGLE_CREDENTIALS_PATH:-./secrets/cbmsc-booker-credentials.json}"
if [ ! -f "$CREDS_PATH" ]; then
    echo -e "${RED}Error: Google credentials file not found at: $CREDS_PATH${NC}"
    echo ""
    echo "Please place the credentials file:"
    echo "  mkdir -p ./secrets"
    echo "  cp cbmsc-booker-credentials.json ./secrets/"
    echo "  chmod 600 ./secrets/cbmsc-booker-credentials.json"
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
