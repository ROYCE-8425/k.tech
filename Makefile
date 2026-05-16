.PHONY: up down build install

# Khởi chạy toàn bộ dự án
up:
	@echo "Starting Backend and AI services via Docker Compose..."
	docker compose up -d
	@echo "Starting Vite Frontend..."
	npm --prefix frontend run dev

# Cài đặt và build lại các package
build:
	@echo "Building Docker images..."
	docker compose build
	@echo "Installing Frontend dependencies..."
	npm --prefix frontend install

# Tắt dự án
down:
	@echo "Stopping Docker Compose services..."
	docker compose down
