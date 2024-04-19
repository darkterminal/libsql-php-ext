#!/bin/bash

echo "Building..."
cargo build

echo "Checking if libs directory exists..."
if [ ! -d "libs" ]; then
    echo "Creating libs directory..."
    mkdir libs
fi

echo "Copying lib into libs directory..."
cp target/debug/libsql_php_client.so libs/

echo "Done! ✨"
