#!/bin/bash

echo "Building..."
cargo build --release

echo "Checking if libs directory exists..."
if [ ! -d "libs" ]; then
    echo "Creating libs directory..."
    mkdir libs
fi

echo "Copying lib into libs directory..."
cp target/release/libsql_php_client.so libs/

echo "Done! ✨"
