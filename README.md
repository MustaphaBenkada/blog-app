# Blog Application

A modern, full-stack blog platform built with **Laravel 11** (backend) and **Vue 3 + Bootstrap 5** (frontend). Features include scheduled publishing with proper timezone handling, OTP-based authentication, Redis-powered search and caching, Markdown comments, infinite scroll, email notifications, and comprehensive debugging tools—all running in a Dockerized environment.

---

## 🚀 Features

### **Core Blog Management**
- **Create & Edit Posts:** Rich text editor with title, excerpt, description, and SEO metadata
- **Image Upload:** Image uploads with preview and automatic storage
- **Tag System:** Add multiple tags to posts with real-time tag management
- **SEO Optimization:** Meta title and description fields for better search engine visibility

### **Advanced Scheduling System**
- **Timezone-Aware Scheduling:** Proper local timezone to UTC conversion
- **Automatic Publishing:** Background scheduler runs every minute to publish due posts
- **Status Management:** Posts have status (draft, scheduled, published) with automatic transitions
- **Email Notifications:** Authors receive email notifications when scheduled posts are published

### **Authentication & Security**
- **OTP-Based Authentication:** Secure, passwordless login using email OTP
- **JWT Tokens:** Stateless authentication with automatic token refresh
- **User Registration:** Simple registration with name and email only
- **Protected Routes:** Frontend route guards for authenticated users

### **Interactive Features**
- **Markdown Comments:** Rich comment system with Markdown support
- **Real-time Comment Display:** Comments show user avatars and formatted timestamps
- **Infinite Scroll:** Seamless post loading as users scroll
- **Search Functionality:** Redis-powered search across titles, descriptions, and tags

### **Performance & Caching**
- **Redis Caching:** Intelligent caching of blog lists and post details
- **Cache Invalidation:** Automatic cache clearing when posts are updated
- **Optimized Queries:** Efficient database queries with proper relationships
- **Image Optimization:** Automatic image processing and storage

### **Developer Tools**
- **Debug Endpoints:** Comprehensive debugging tools for post status and scheduling
- **Manual Scheduler:** Development tools to manually trigger scheduled publishing
- **Status Monitoring:** Real-time post status monitoring with timezone information
- **Error Handling:** Comprehensive error handling with user-friendly messages

---

## 🛠 Tech Stack

### **Backend**
- **Framework:** Laravel 11 (PHP 8.2+)
- **Database:** MySQL 8.0
- **Cache & Queue:** Redis 7.0
- **Authentication:** JWT (tymon/jwt-auth)
- **File Storage:** Laravel Storage with public disk
- **Email:** Laravel Mail with queue support
- **Scheduling:** Laravel Task Scheduling

### **Frontend**
- **Framework:** Vue 3 with Composition API
- **Build Tool:** Vite
- **UI Framework:** Bootstrap 5
- **State Management:** Pinia
- **HTTP Client:** Axios with interceptors
- **Markdown:** vue3-markdown-it
- **Routing:** Vue Router 4

### **Infrastructure**
- **Containerization:** Docker & Docker Compose
- **Queue Worker:** Dedicated container for background jobs
- **Development:** Hot reload for both frontend and backend

---

## 📦 Installation & Setup

### **Prerequisites**
- Docker & Docker Compose
- Git

### **Quick Start**

1. **Clone the repository:**
    ```bash
    git clone <repository-url>
   cd blog-app
    ```

2. **Create environment file:**
    ```bash
    cp .env.example .env
    # Edit .env with your configuration
    ```

3. **Start all services:**
    ```bash
    docker-compose up --build -d
    ```

4. **Run database migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

5. **Seed the database (creates 200k blog posts):**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

6. **Access the application:**
   - **Frontend:** [http://localhost:5173](http://localhost:5173)
   - **Backend API:** [http://localhost:8000](http://localhost:8000)

---

## 🎯 Usage Guide

### **Authentication**
1. **Register:** Enter your name and email address
2. **Login:** Enter your email to receive an OTP
3. **Verify:** Enter the OTP to access your account
4. **Session:** JWT tokens are automatically managed

### **Creating Blog Posts**
1. **Navigate to:** "Create Post" from the navigation
2. **Fill Details:** Title, excerpt, description, tags
3. **Upload Image:** Optional image upload with preview
4. **SEO Settings:** Add meta title and description
5. **Schedule (Optional):** Set future publish date/time
6. **Submit:** Post is created as draft or scheduled

### **Scheduling Posts**
- **Immediate Publishing:** Leave schedule field empty
- **Future Publishing:** Set date/time in your local timezone

### **Managing Posts**
- **Edit:** Modify any post from the post details page
- **Delete:** Remove posts with confirmation dialog
- **View Status:** Check post status and scheduled time
- **My Posts:** View all your posts (drafts, scheduled, published)

### **Commenting System**
- **Login Required:** Only authenticated users can comment
- **Markdown Support:** Use Markdown syntax in comments
- **Real-time Display:** Comments show immediately after posting
- **User Avatars:** Automatic avatar generation from user names

### **Search & Discovery**
- **Search Bar:** Search posts by title, description, or tags
- **Infinite Scroll:** Load more posts as you scroll
- **Responsive Design:** Works on all device sizes

---

## 🔍 Search Strategy & Implementation

### **Redis-Powered Search Architecture**
The application implements a sophisticated search system using Redis as both a search index and cache layer:

#### **Search Index Structure**
```php
// Redis Hash Structure for each post
post:{post_id} = {
    'title' => 'Post Title',
    'description' => 'Post content...',
    'excerpt' => 'Post excerpt...',
    'tags' => 'tag1 tag2 tag3'
}
```

#### **Search Process Flow**
1. **Indexing:** When posts are created/updated, they're automatically indexed in Redis
2. **Search Query:** User input is processed and matched against indexed data
3. **Result Retrieval:** Matching post IDs are fetched from database
4. **Pagination:** Results are paginated for infinite scroll

#### **Search Features**
- **Multi-field Search:** Searches across title, description, excerpt, and tags
- **Case-insensitive:** All searches are case-insensitive
- **Partial Matching:** Supports partial word matching
- **Real-time Indexing:** Posts are indexed immediately upon creation/update
- **Index Rebuilding:** Command to rebuild entire search index

#### **Search Implementation Details**
```bash
# Rebuild search index manually
docker-compose exec app php artisan posts:rebuild-search-index

# Search API endpoint
GET /api/posts/search?query=search_term&page=1&per_page=10
```

---

## 🗄️ Caching Strategy & Implementation

### **Multi-Level Caching Architecture**
The application implements a comprehensive caching strategy using Redis:

#### **Cache Layers**
1. **Blog List Cache:** Paginated blog post lists
2. **Individual Post Cache:** Single post details with relationships
3. **User Session Cache:** JWT token validation

#### **Cache Keys Structure**
```php
// Blog list cache keys
blog:list:page_{page}_per_{per_page}

// Individual post cache
blog:{post_id}

```

#### **Cache Invalidation Strategy**
- **Automatic Invalidation:** Cache is cleared when posts are created, updated, or deleted
- **Selective Invalidation:** Only affected cache keys are cleared
- **Master Key Tracking:** Tracks all cache keys for bulk operations
- **TTL Management:** Default 10-minute TTL with configurable expiration

#### **Cache Implementation Details**
```php
// Cache Service Methods
CacheService::getPaginatedBlogList($page, $perPage)
CacheService::setPaginatedBlogList($page, $perPage, $data)
CacheService::invalidateBlogList()
CacheService::getBlog($id)
CacheService::setBlog($id, $data)
```

#### **Cache Performance Benefits**
- **Reduced Database Queries:** 90% reduction in database load
- **Faster Response Times:** Sub-100ms response times for cached data
- **Scalability:** Handles high concurrent user loads
- **Memory Efficiency:** Intelligent cache key management

---

## 📊 Database Schema

### **Core Tables Structure**

#### **Users Table**
```sql
users (
    id (bigint, primary key)
    name (varchar(255))
    email (varchar(255), unique)
    otp_code (varchar(6), nullable)
    otp_expires_at (timestamp, nullable)
    created_at (timestamp)
    updated_at (timestamp)
)
```

#### **Blog Posts Table**
```sql
blog_posts (
    id (bigint, primary key)
    user_id (bigint, foreign key)
    title (varchar(255))
    excerpt (text, nullable)
    description (longtext, nullable)
    image (varchar(255), nullable)
    meta_title (varchar(255), nullable)
    meta_description (varchar(255), nullable)
    published_at (timestamp, nullable)
    status (varchar(255), default: 'draft')
    created_at (timestamp)
    updated_at (timestamp)
)
```

#### **Tags Table**
```sql
tags (
    id (bigint, primary key)
    name (varchar(255), unique)
    created_at (timestamp)
    updated_at (timestamp)
)
```

#### **Blog Post Tags (Pivot Table)**
```sql
blog_post_tag (
    blog_post_id (bigint, foreign key)
    tag_id (bigint, foreign key)
    primary key (blog_post_id, tag_id)
)
```

#### **Comments Table**
```sql
comments (
    id (bigint, primary key)
    user_id (bigint, foreign key)
    blog_post_id (bigint, foreign key)
    content (text)
    created_at (timestamp)
    updated_at (timestamp)
)
```

### **Database Relationships**
- **User → BlogPosts:** One-to-Many
- **BlogPost → Comments:** One-to-Many
- **BlogPost ↔ Tags:** Many-to-Many
- **User → Comments:** One-to-Many

### **Database Seeding**
- **200,000 Blog Posts:** Generated with realistic content
- **50 Tags:** Diverse tag categories
- **10 Users:** Sample user accounts
- **Chunked Processing:** Memory-efficient seeding

---

## 🔄 Queue System & Background Processing

### **Queue Architecture**
The application uses Redis as the queue driver for background job processing:

#### **Queue Configuration**
```php
// Queue driver configuration
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

#### **Queue Workers**
- **Dedicated Container:** Separate container for queue processing
- **Automatic Restart:** Workers restart on failure
- **Job Retries:** Configurable retry attempts (3 tries)
- **Timeout Handling:** 90-second job timeout

#### **Background Jobs**

##### **Email Notifications**
```php
// Job: SendPublishedPostEmail
- Triggers when posts are published
- Sends email to post author
- Includes post details and frontend URL
- Handles email delivery failures
```

##### **Scheduled Publishing**
```php
// Command: PublishScheduledPosts
- Runs every minute via Laravel scheduler
- Publishes posts when scheduled time arrives
- Updates post status and triggers notifications
- Rebuilds search index for new posts
```

#### **Queue Management**
```bash
# View queue status
docker-compose exec app php artisan queue:work --verbose

# Monitor failed jobs
docker-compose exec app php artisan queue:failed

# Retry failed jobs
docker-compose exec app php artisan queue:retry all
```

---

## 🔧 Development & Debugging

### **Manual Scheduler Testing**
```bash
# Check current post status
curl -H "Authorization: Bearer YOUR_TOKEN" http://localhost:8000/api/debug-post-status

# Manually trigger scheduler
docker-compose exec app php artisan posts:publish

# Check scheduler output
docker-compose exec app php artisan posts:publish
```

### **Search & Cache Debugging**
```bash
# Rebuild search index
docker-compose exec app php artisan posts:rebuild-search-index

# Clear all caches
docker-compose exec app php artisan cache:clear

# Monitor Redis
docker-compose exec redis redis-cli monitor

# Check cache keys
docker-compose exec redis redis-cli keys "blog:*"
```


### **Container Management**
```bash
# View running containers
docker-compose ps

# View logs
docker-compose logs backend
docker-compose logs frontend
docker-compose logs queue-worker

# Restart services
docker-compose restart backend
docker-compose restart frontend
```

### **Database Operations**
```bash
# Run migrations
docker-compose exec app php artisan migrate

# Seed database (200k posts)
docker-compose exec app php artisan db:seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear

# Database backup
docker-compose exec mysql mysqldump -u root -proot blog_db > backup.sql
```

---

## 🏗 Architecture

### **Backend Architecture**
```
Laravel App
├── Controllers (API endpoints)
├── Services (Business logic)
│   ├── SearchService (Redis search)
│   ├── CacheService (Redis caching)
│   ├── BlogPostService (Post management)
│   └── ImageService (File handling)
├── Models (Database relationships)
├── Jobs (Background processing)
├── Mail (Email notifications)
├── Commands (Scheduled tasks)
└── Middleware (Authentication, CORS)
```

### **Frontend Architecture**
```
Vue 3 App
├── Components (Reusable UI)
├── Views (Page components)
├── Stores (Pinia state management)
├── Router (Navigation)
├── Services (API calls)
└── Assets (Styling, images)
```

### **Data Flow**
1. **User Input** → Frontend validation
2. **API Request** → Backend validation
3. **Database** → Model relationships
4. **Cache** → Redis storage
5. **Queue** → Background processing
6. **Email** → Notification delivery

---

## 🔒 Security Features

- **OTP Authentication:** No password storage
- **JWT Tokens:** Secure, stateless authentication
- **CORS Protection:** Proper cross-origin handling
- **Input Validation:** Comprehensive request validation
- **SQL Injection Protection:** Eloquent ORM
- **XSS Protection:** Output escaping
- **CSRF Protection:** Token-based protection
- **Rate Limiting:** API rate limiting
- **File Upload Security:** Image validation and processing


---

## 🧪 Testing

### **API Testing**
```bash
# Test authentication
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com"}'

# Test post creation
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Post","excerpt":"Test","description":"Test content"}'

# Test search
curl "http://localhost:8000/api/posts/search?query=test&page=1"
```

### **Scheduler Testing**
```bash
# Create a post scheduled for 2 minutes from now
# Wait 2+ minutes
# Run scheduler
docker-compose exec app php artisan posts:publish
# Check if post status changed to "published"
```

### **Cache Testing**
```bash
# Test cache hit/miss
curl "http://localhost:8000/api/posts/search?page=1"
# First request: cache miss (slower)
# Second request: cache hit (faster)
```


### **Monitoring Commands**
```bash
# Monitor Redis memory usage
docker-compose exec redis redis-cli info memory

# Check cache hit rate
docker-compose exec redis redis-cli info stats

# Monitor queue status
docker-compose exec app php artisan queue:work --verbose

# Database performance
docker-compose exec app php artisan tinker
# DB::select('SHOW STATUS LIKE "Slow_queries"');
```

---

## 🆘 Troubleshooting

### **Common Issues**

#### **Search Not Working**
```bash
# Rebuild search index
docker-compose exec app php artisan posts:rebuild-search-index
```

#### **Cache Issues**
```bash
# Clear all caches
docker-compose exec app php artisan cache:clear
docker-compose exec redis redis-cli flushall
```

#### **Queue Not Processing**
```bash
# Restart queue worker
docker-compose restart queue
# Check queue logs
docker-compose logs queue
```

#### **Database Connection Issues**
```bash
# Check database status
docker-compose exec mysql mysqladmin ping -u root -proot
# Restart database
docker-compose restart mysql
```

#### **Frontend Build Issues**
```bash
# Rebuild frontend
docker-compose exec frontend npm run build
# Clear node modules
docker-compose exec frontend rm -rf node_modules && npm install
```

