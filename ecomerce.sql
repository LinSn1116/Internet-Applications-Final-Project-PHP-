create database ecommerce;

use ecommerce;

create table users (
    id int(10) auto_increment primary key,
    username varchar(50) not null unique,
    password varchar(255) not null,
    is_admin boolean default false,
    login_count int(10) default 0,
    last_login timestamp null default null,
    created_at timestamp default current_timestamp
);

create table products (
    id int(10) auto_increment primary key,
    name varchar(100) not null,
    description text,
    price decimal(10, 2) not null,
    stock int(10) not null,
    category varchar(50),
    tags varchar(255),
    created_at timestamp default current_timestamp
);

create table cart (
    id int(10) auto_increment primary key,
    user_id int(10) not null,
    product_id int(10) not null,
    quantity int(10) not null,
    created_at timestamp default current_timestamp,
    foreign key (user_id) references users(id),
    foreign key (product_id) references products(id)
);

create table orders (
    id int(10) auto_increment primary key,
    user_id int(10) not null,
    total_price decimal(10, 2) not null,
    status varchar(50) default '準備中',
    created_at timestamp default current_timestamp,
    foreign key (user_id) references users(id)
);

create table order_details (
    id int(10) auto_increment primary key,
    order_id int(10) not null,
    product_id int(10) not null,
    quantity int(10) not null,
    price decimal(10, 2) not null,
    foreign key (order_id) references orders(id),
    foreign key (product_id) references products(id)
);

INSERT INTO users (id, username, password, is_admin) VALUES (0,'root', 'root', TRUE);

-- 插入初始產品數據
insert into products (id, name, price, stock, description, category, tags) values
(1, '鉛筆', 10, 100, '普通鉛筆', '文具', '文具,鉛筆'),
(2, '原子筆', 28, 100, '普通原子筆', '文具', '文具,原子筆'),
(3, '修正帶', 28, 100, '修正帶', '文具', '文具,修正帶'),
(4, '削鉛筆機', 300, 100, '削鉛筆機', '文具', '文具,削鉛筆機'),
(5, '膠帶', 90, 100, '透明膠帶', '文具', '文具,膠帶'),
(6, '尺(15cm)', 10, 100, '15厘米尺', '文具', '文具,尺'),
(7, '尺(30cm)', 20, 100, '30厘米尺', '文具', '文具,尺'),
(8, '美工刀', 35, 100, '美工刀', '文具', '文具,美工刀');
