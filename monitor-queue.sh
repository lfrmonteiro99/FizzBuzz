#!/bin/bash

echo "Monitoring Redis queue for new messages..."
echo "Press Ctrl+C to stop"

while true; do
    # Get the number of messages in the queue
    count=$(docker-compose exec redis redis-cli LLEN messages)
    
    if [ "$count" -gt "0" ]; then
        echo "New message(s) detected at $(date)"
        echo "Message content:"
        docker-compose exec redis redis-cli LINDEX messages 0
        echo "----------------------------------------"
    fi
    
    # Wait for 1 second before checking again
    sleep 1
done 