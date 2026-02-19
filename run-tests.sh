#!/bin/bash

# Test runner script for Laravel Sail environment
# Usage: ./run-tests.sh [options]
# Options:
#   --unit          Run only unit tests
#   --feature       Run only feature tests  
#   --integration   Run only integration tests
#   --browser       Run only Dusk browser tests
#   --coverage      Run with coverage report
#   --filter        Filter tests by name

set -e

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${GREEN}AI Agent Test Runner${NC}"
echo "====================="
echo ""

# Check if Sail is available
if ! command -v ./vendor/bin/sail &> /dev/null; then
    echo -e "${RED}Error: Laravel Sail not found. Make sure you're in the project root.${NC}"
    exit 1
fi

# Parse arguments
TEST_SUITE=""
COVERAGE=""
FILTER=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --unit)
            TEST_SUITE="--testsuite=Unit"
            shift
            ;;
        --feature)
            TEST_SUITE="--testsuite=Feature"
            shift
            ;;
        --integration)
            TEST_SUITE="--testsuite=Integration"
            shift
            ;;
        --browser)
            TEST_SUITE="--testsuite=Browser"
            shift
            ;;
        --coverage)
            COVERAGE="--coverage --min=90"
            shift
            ;;
        --filter)
            FILTER="--filter=$2"
            shift 2
            ;;
        *)
            echo -e "${YELLOW}Unknown option: $1${NC}"
            shift
            ;;
    esac
done

# Run tests through Sail
echo -e "${YELLOW}Running tests through Laravel Sail...${NC}"
echo ""

if [ -n "$TEST_SUITE" ]; then
    echo "Test Suite: $TEST_SUITE"
fi

if [ -n "$COVERAGE" ]; then
    echo "Coverage: Enabled (min 90%)"
fi

if [ -n "$FILTER" ]; then
    echo "Filter: $FILTER"
fi

echo ""

# Build the command
CMD="./vendor/bin/sail pest"

if [ -n "$TEST_SUITE" ]; then
    CMD="$CMD $TEST_SUITE"
fi

if [ -n "$COVERAGE" ]; then
    CMD="$CMD $COVERAGE"
fi

if [ -n "$FILTER" ]; then
    CMD="$CMD $FILTER"
fi

echo "Command: $CMD"
echo ""

# Execute the command
$CMD

echo ""
echo -e "${GREEN}Tests completed!${NC}"
