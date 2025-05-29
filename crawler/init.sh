#!/bin/sh

while true; do
  start_time=$(date +%s)

  echo "[crawler] starting cycle: $(date)"

  php get_tags.php

  echo "[crawler] done: $(date)"

  end_time=$(date +%s)
  elapsed=$((end_time - start_time))
  sleep_time=$((300 - elapsed))

  if [ $sleep_time -gt 0 ]; then
    echo "[crawler] sleeping $sleep_time seconds"
    sleep $sleep_time
  else
    echo "[crawler] no sleep (cycle took $elapsed seconds)"
  fi
done