CREATE DATABASE IF NOT EXISTS db_tiket_konser DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE db_tiket_konser;

CREATE TABLE tbl_users (
  id_user int(11) NOT NULL,
  nama_lengkap varchar(100) NOT NULL,
  email varchar(100) NOT NULL,
  password varchar(255) NOT NULL,
  role enum('admin','user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO tbl_users (id_user, nama_lengkap, email, password, role) VALUES
(1, 'Administrator', 'admin@konser.com', '$2y$10$Io/A6jsY0VJYYau7EVKp1.nI7LDMSTq5jV5GgzwYaG4ZBO4jKRjwi', 'admin'),
(2, 'Zaskia', 'jihan@email.com', '$2y$10$Jb2J.i0BEaiWvaed38tK6.8lI806/Kqpm3IpV20OdubotmmrrozBe', 'user');

CREATE TABLE tbl_konser (
  id_konser int(11) NOT NULL,
  nama_konser varchar(150) NOT NULL,
  lokasi varchar(100) NOT NULL,
  tanggal_waktu datetime NOT NULL,
  deskripsi text DEFAULT NULL,
  gambar varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tbl_kategori_tiket (
  id_kategori int(11) NOT NULL,
  id_konser int(11) NOT NULL,
  nama_kategori varchar(50) NOT NULL COMMENT 'Contoh: VIP, Reguler, Festival',
  harga decimal(10,2) NOT NULL,
  stok int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tbl_pemesanan (
  id_pemesanan int(11) NOT NULL,
  id_user int(11) NOT NULL,
  tanggal_pemesanan timestamp NOT NULL DEFAULT current_timestamp(),
  total_harga decimal(10,2) NOT NULL,
  status_pembayaran enum('Menunggu','Lunas','Batal') NOT NULL DEFAULT 'Menunggu',
  bukti_pembayaran varchar(255) DEFAULT NULL COMMENT 'Nama file bukti bayar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE tbl_detail_pemesanan (
  id_detail int(11) NOT NULL,
  id_pemesanan int(11) NOT NULL,
  id_kategori_tiket int(11) NOT NULL,
  jumlah_tiket int(5) NOT NULL,
  harga_saat_pesan decimal(10,2) NOT NULL COMMENT 'Harga di-copy saat checkout'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table tbl_users
--
ALTER TABLE tbl_users
  ADD PRIMARY KEY (id_user),
  ADD UNIQUE KEY email (email);

--
-- Indexes for table tbl_konser
--
ALTER TABLE tbl_konser
  ADD PRIMARY KEY (id_konser);

--
-- Indexes for table tbl_kategori_tiket
--
ALTER TABLE tbl_kategori_tiket
  ADD PRIMARY KEY (id_kategori),
  ADD KEY id_konser (id_konser);

--
-- Indexes for table tbl_pemesanan
--
ALTER TABLE tbl_pemesanan
  ADD PRIMARY KEY (id_pemesanan),
  ADD KEY id_user (id_user);

--
-- Indexes for table tbl_detail_pemesanan
--
ALTER TABLE tbl_detail_pemesanan
  ADD PRIMARY KEY (id_detail),
  ADD KEY id_pemesanan (id_pemesanan),
  ADD KEY id_kategori_tiket (id_kategori_tiket);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table tbl_users
--
ALTER TABLE tbl_users
  MODIFY id_user int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table tbl_konser
--
ALTER TABLE tbl_konser
  MODIFY id_konser int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table tbl_kategori_tiket
--
ALTER TABLE tbl_kategori_tiket
  MODIFY id_kategori int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table tbl_pemesanan
--
ALTER TABLE tbl_pemesanan
  MODIFY id_pemesanan int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table tbl_detail_pemesanan
--
ALTER TABLE tbl_detail_pemesanan
  MODIFY id_detail int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table tbl_kategori_tiket
--
ALTER TABLE tbl_kategori_tiket
  ADD CONSTRAINT tbl_kategori_tiket_ibfk_1 FOREIGN KEY (id_konser) REFERENCES tbl_konser (id_konser) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table tbl_pemesanan
--
ALTER TABLE tbl_pemesanan
  ADD CONSTRAINT tbl_pemesanan_ibfk_1 FOREIGN KEY (id_user) REFERENCES tbl_users (id_user) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table tbl_detail_pemesanan
--
ALTER TABLE tbl_detail_pemesanan
  ADD CONSTRAINT tbl_detail_pemesanan_ibfk_1 FOREIGN KEY (id_pemesanan) REFERENCES tbl_pemesanan (id_pemesanan) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT tbl_detail_pemesanan_ibfk_2 FOREIGN KEY (id_kategori_tiket) REFERENCES tbl_kategori_tiket (id_kategori) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
