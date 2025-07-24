-- Table: clients
DROP TABLE IF EXISTS clients;
CREATE TABLE clients (
                         id INT PRIMARY KEY,
                         ci VARCHAR(255),
                         first_name VARCHAR(255),
                         last_name VARCHAR(255),
                         address VARCHAR(255),
                         phone VARCHAR(255),
                         reliability_status VARCHAR(255),
                         status VARCHAR(255),
                         detail TEXT,
                         created_at VARCHAR(255),
                         updated_at VARCHAR(255)
);

-- Table: loans
DROP TABLE IF EXISTS loans;
CREATE TABLE loans (
                       id INT PRIMARY KEY,
                       code VARCHAR(20),
                       weight DECIMAL(10,2),
                       jewelry_amount DECIMAL(10,2),
                       interest DECIMAL(10,2),
                       detail TEXT,
                       created_at VARCHAR(255),
                       state VARCHAR(10),
                       user_id INT,
                       client_id INT,
                       updated_at VARCHAR(255)
);

-- Table: amounts
DROP TABLE IF EXISTS amounts;
CREATE TABLE amounts (
                         id INT PRIMARY KEY,
                         payment DECIMAL(10,2),
                         detail TEXT,
                         loan_id INT,
                         created_at VARCHAR(255),
                         updated_at VARCHAR(255)
);

-- Table: orders
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
                        id INT PRIMARY KEY,
                        code VARCHAR(50),
                        detail TEXT,
                        date_end DATE,
                        total DECIMAL(10,2),
                        state VARCHAR(5),
                        user_id INT,
                        client_id INT,
                        created_at VARCHAR(255),
                        updated_at VARCHAR(255)
);

-- Table: loan_payments
DROP TABLE IF EXISTS loan_payments;
CREATE TABLE loan_payments (
                               id INT PRIMARY KEY,
                               payment DECIMAL(10,2),
                               detail TEXT,
                               loan_id INT,
                               created_at VARCHAR(255),
                               updated_at VARCHAR(255)
);

-- Table: loan_capital_payments
DROP TABLE IF EXISTS loan_capital_payments;
CREATE TABLE loan_capital_payments (
                                       id INT PRIMARY KEY,
                                       payment DECIMAL(10,2),
                                       detail TEXT,
                                       loan_id INT,
                                       created_at VARCHAR(255),
                                       updated_at VARCHAR(255)
);

-- Table: daily_histories
DROP TABLE IF EXISTS daily_histories;
CREATE TABLE daily_histories (
                                 id INT PRIMARY KEY,
                                 amount DECIMAL(10,2),
                                 description TEXT,
                                 type CHAR(1),
                                 user_id INT,
                                 created_at VARCHAR(255),
                                 updated_at VARCHAR(255)
);
