.PHONY: help install test test-coverage lint analyse rector rector-dry quality quality-fix clean

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Installation
install: ## Install composer dependencies
	composer install

update: ## Update composer dependencies
	composer update

# Testing
test: ## Run tests with Pest
	composer test

test-coverage: ## Run tests with coverage (minimum 80%)
	composer test:coverage

# Code Quality
lint: ## Run PHP linter
	composer lint

analyse: ## Run PHPStan static analysis
	composer analyse

analyse-baseline: ## Generate PHPStan baseline
	composer analyse:baseline

# Rector
rector: ## Apply Rector refactorings
	composer rector

rector-dry: ## Preview Rector changes (dry-run)
	composer rector:dry

# Combined Commands
quality: ## Run all quality checks (lint + analyse + rector-dry + test)
	composer quality

quality-fix: ## Fix code quality issues and run tests (rector + test)
	composer quality:fix

# Git Commands
status: ## Show git status
	@git status

diff: ## Show git diff
	@git diff

# Cleanup
clean: ## Clear caches and temporary files
	@rm -rf vendor/
	@rm -f composer.lock
	@echo "Cleaned vendor/ and composer.lock"

clear-cache: ## Clear composer cache
	composer clear-cache

# Development Workflow
check: quality ## Alias for quality check

fix: quality-fix ## Alias for quality fix

# CI/CD Simulation
ci: ## Simulate CI pipeline locally
	@echo "==> Running linter..."
	@make lint
	@echo ""
	@echo "==> Running PHPStan analysis..."
	@make analyse
	@echo ""
	@echo "==> Running Rector dry-run..."
	@make rector-dry
	@echo ""
	@echo "==> Running tests with coverage..."
	@make test-coverage
	@echo ""
	@echo "✅ CI pipeline completed successfully!"

# Pre-commit Workflow
pre-commit: ## Run checks before committing
	@echo "==> Running Rector to fix code..."
	@make rector
	@echo ""
	@echo "==> Running tests..."
	@make test
	@echo ""
	@echo "==> Running static analysis..."
	@make analyse
	@echo ""
	@echo "✅ Pre-commit checks passed!"

# Security
audit: ## Run composer security audit
	composer audit

# Information
info: ## Show project information
	@echo "Project: Vantage - Laravel Queue Monitoring"
	@echo "PHP Version: $(shell php -v | head -n 1)"
	@echo "Composer Version: $(shell composer --version)"
	@echo ""
	@echo "Available commands:"
	@make help
