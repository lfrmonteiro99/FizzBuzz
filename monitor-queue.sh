#!/bin/bash

echo "Monitoring Redis stream for new messages..."
echo "Press Ctrl+C to stop"

# Get the last message ID
LAST_ID="0-0"

while true; do
    # Get the latest message ID
    LATEST_ID=$(docker-compose exec redis redis-cli XREVRANGE messages + - COUNT 1 | grep -o '[0-9]*-[0-9]*' | head -n 1)
    
    if [ ! -z "$LATEST_ID" ] && [ "$LATEST_ID" != "$LAST_ID" ]; then
        # Read the message
        MESSAGE=$(docker-compose exec redis redis-cli XRANGE messages $LAST_ID $LATEST_ID)
        
        if [ ! -z "$MESSAGE" ]; then
            echo "New message detected at $(date)"
            echo "$MESSAGE" | jq '.' 2>/dev/null || echo "$MESSAGE"
            echo "----------------------------------------"
            
            # Update the last message ID
            LAST_ID=$LATEST_ID
        fi
    fi
    
    # Sleep for 1 second before checking again
    sleep 1
done 