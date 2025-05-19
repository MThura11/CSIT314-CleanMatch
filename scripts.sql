-- Create table
CREATE DATABASE homeCleaningService;
USE DATABASE homeCleaningService;
-- Create users table
CREATE TABLE users (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
	userType CHARACTER NOT NULL
);

-- Create homeCleaners table
CREATE TABLE homeCleaners (
    homeCleanerID INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL, -- Reference to the main users table                                                                                                                   
    fullName VARCHAR(100) NOT NULL,
    phoneNumber VARCHAR(20),
    location TEXT,
    experienceYears INT,
    availability VARCHAR(100) DEFAULT 'available',
    hourlyRate DECIMAL(6, 2),
    totalRating DECIMAL(3,2) DEFAULT 0.0,
    email VARCHAR(100),
    FOREIGN KEY (userId) REFERENCES users(userId)
);

-- Create orders table
CREATE TABLE orders (
    orderId INT AUTO_INCREMENT PRIMARY KEY,
    homeOwnerId INT NOT NULL,
    homeCleanerID INT NOT NULL,
    orderDate DATE,
    startTime TIME,
    durationHours INT,
	serviceName VARCHAR(50),
    status VARCHAR(50) DEFAULT 'pending',
    totalPrice DECIMAL(10, 2) NOT NULL,
    userAddress TEXT,
    rating DECIMAL(3,2) ,
    paymentMethod VARCHAR(50),
    FOREIGN KEY (homeCleanerID) REFERENCES homecleaners(homeCleanerID),
    FOREIGN KEY (homeOwnerId) REFERENCES users(userId)
);

-- Create favorite table 
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    homeCleanerID INT NOT NULL,
    UNIQUE KEY unique_favorite (userId, homeCleanerID),
    FOREIGN KEY (userId) REFERENCES users(userId) ON DELETE CASCADE,
    FOREIGN KEY (homeCleanerID) REFERENCES homecleaners(homeCleanerID) ON DELETE CASCADE
);

-- Create services table 
CREATE TABLE services (
    serviceID INT AUTO_INCREMENT PRIMARY KEY,
    serviceName VARCHAR(100) NOT NULL UNIQUE
);

-- Create cleanerServices table 
CREATE TABLE cleanerServices (
    homeCleanerID INT NOT NULL,
    serviceID INT NOT NULL,
    PRIMARY KEY (homeCleanerID, serviceID),
    CONSTRAINT fk_cs_cleaner FOREIGN KEY (homeCleanerID) REFERENCES homecleaners(homeCleanerID) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cs_service FOREIGN KEY (serviceID) REFERENCES services(serviceID) ON DELETE CASCADE ON UPDATE CASCADE
);
