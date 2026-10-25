#!/bin/bash

clear

while true; do
    echo
    echo "Suitefish Docker Image Script"
    echo
    echo "Choose an option by entering a number (1-6):"
    echo "1. Rebuild the Image without Cache"
    echo "2. Docker Compose Up"
    echo "3. Docker Compose Restart"
    echo "4. Docker Compose Down"
    echo "5. Docker Compose Purge Content (CAUTION)"
    echo "6. Exit"
    echo
    read -p "Enter your choice: " choice

    case $choice in
        1)
            echo
            docker compose build --no-cache
            ;;
        2)
            echo
            docker compose -f ./docker-compose.yml up -d
            ;;
        3)
            echo
            docker compose -f ./docker-compose.yml restart
            ;;
        4)
            echo
            docker compose -f ./docker-compose.yml down
            ;;
        5)
            echo
            docker compose -f ./docker-compose.yml down -v --rmi all --remove-orphans
            ;;
        6)
            echo
            echo "Suitefish Docker Script reached its End of File"
            echo "Goodbye!"
            echo
            exit 0
            ;;
        *)
            echo "Invalid choice! Please try again."
            sleep 1
            clear
            continue
            ;;
    esac

    echo
    read -p "Press Enter to return to menu..."
    clear
done
