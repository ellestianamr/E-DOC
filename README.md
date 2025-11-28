E-Doc
============

## 📁 Structure Directory (Folder)
```
.
├── bower_components/
├── dist/
├── pages/
├── plugins/
├── script/
├── screenshot/
├── template/
└── uploads/
```
noted: perhatikan dimana `bower_components` diekstrak

## 🖥️ Live Preview
Check the live demo here: https://e-doc.infinityfreeapp.com
| username | password |
|--------------|---------|
| admin        | admin   |
| sekretariat1 | 12456   |
| industri1    | 12456   |
| hubinsyaker1 | 12456   |
| pptk1        | 12456   |

## Database Setup
Create Database: `e-doc_db`

#### Buat tabel roles
```
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL
);
```
#### Isi data role-nya
```
INSERT INTO roles (role_name) VALUES
('admin'),
('sekretariat'),
('industri'),
('hubinsyaker'),
('pptk');
```
#### Buat tabel users
```
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);
```
NOTED: Jika sebelumnya sudah ada table `users` maka tambah column seperti berikut:
```
ALTER TABLE users
ADD COLUMN foto_profil VARCHAR(255) NULL,
ADD COLUMN nik VARCHAR(30) NULL UNIQUE,
ADD COLUMN nama VARCHAR(150) NULL,
ADD COLUMN email VARCHAR(150) NULL,
ADD COLUMN no_hp VARCHAR(20) NULL,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
```
NOTED: Jika belum punya table `users`
```
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role_id INT,

  -- Tambahan kolom baru
  foto_profil VARCHAR(255) NULL,
  nik VARCHAR(30) NULL UNIQUE,
  nama VARCHAR(150) NULL,
  email VARCHAR(150) NULL,
  no_hp VARCHAR(20) NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (role_id) REFERENCES roles(id)
);
```
#### Isi data user-nya
```
INSERT INTO users (username, password, role_id) VALUES
('admin', '$2y$10$JjwiLWkbwkepRANJlK9r5ua84oW8bSlPzJ3dQVe1En2P/ty6zIBJS', 1),
('sekretariat1', '$2y$10$nDG2HhK0uQ6N7IUJtaSuBuqUhdAp4QKjlaKSw7W2XTqBGIQrZSRuG', 2),
('industri1', '$2y$10$nDG2HhK0uQ6N7IUJtaSuBuqUhdAp4QKjlaKSw7W2XTqBGIQrZSRuG', 3),
('hubinsyaker1', '$2y$10$nDG2HhK0uQ6N7IUJtaSuBuqUhdAp4QKjlaKSw7W2XTqBGIQrZSRuG', 4),
('pptk1', '$2y$10$nDG2HhK0uQ6N7IUJtaSuBuqUhdAp4QKjlaKSw7W2XTqBGIQrZSRuG', 5);
```
noted: password admin = admin, selain admin 123456

#### Buat tabel dokumen
```
CREATE TABLE dokumen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nama_dokumen VARCHAR(255) NOT NULL,
  bidang VARCHAR(100) NOT NULL,
  nama_file VARCHAR(255) NOT NULL,
  ukuran_file VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_user FOREIGN KEY (id_user) REFERENCES users(id)
);
```

#### Buat tabel notifications
```
CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT,
  message VARCHAR(255),
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```


