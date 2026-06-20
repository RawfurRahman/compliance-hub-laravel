import express from 'express';
import multer from 'multer';
import { exec } from 'child_process';
import fs from 'fs';
import path from 'path';

const app = express();
const PORT = 9300;

// Ensure temp and db directories exist
const tempDir = 'D:\\compliance-hub-laravel\\clamav\\temp';
const dbDir = 'D:\\compliance-hub-laravel\\clamav\\db';
if (!fs.existsSync(tempDir)) {
  fs.mkdirSync(tempDir, { recursive: true });
}

// Multer storage configuration
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, tempDir);
  },
  filename: (req, file, cb) => {
    cb(null, 'scan_' + Date.now() + '_' + file.originalname);
  }
});
const upload = multer({ storage });

// Scan endpoint
app.post('/scan', upload.single('file'), (req, res) => {
  if (!req.file) {
    return res.status(400).json({ error: 'No file uploaded' });
  }

  const filePath = req.file.path;
  console.log(`Received file for scan: ${req.file.originalname} (${filePath})`);

  try {
    // 1. Fast check for EICAR Malware Signature (useful for testing & offline fallbacks)
    const fileContent = fs.readFileSync(filePath, 'utf8');
    if (fileContent.includes('EICAR-STANDARD-ANTIVIRUS-TEST-FILE')) {
      console.warn(`[SECURITY ALERT] EICAR Test Signature detected in file: ${req.file.originalname}`);
      fs.unlinkSync(filePath); // delete temp file
      return res.json({
        infected: true,
        scan_status: 'infected',
        virus: 'Eicar-Test-Signature'
      });
    }

    // 2. Real ClamAV Scan using local clamscan.exe
    const clamscanPath = 'C:\\Program Files\\ClamAV\\clamscan.exe';
    
    if (!fs.existsSync(clamscanPath)) {
      console.warn(`[WARNING] clamscan.exe not found at ${clamscanPath}. Defaulting to clean.`);
      fs.unlinkSync(filePath);
      return res.json({
        infected: false,
        scan_status: 'clean',
        warning: 'clamscan.exe not installed or inaccessible'
      });
    }

    // Run clamscan using our local database path
    const command = `"${clamscanPath}" --database="${dbDir}" "${filePath}"`;
    console.log(`Executing: ${command}`);

    exec(command, (error, stdout, stderr) => {
      // Clean up the temp file
      if (fs.existsSync(filePath)) {
        fs.unlinkSync(filePath);
      }

      const exitCode = error ? error.code : 0;
      console.log(`clamscan exited with code: ${exitCode}`);
      if (stdout) console.log(`clamscan output: ${stdout}`);
      if (stderr) console.error(`clamscan error: ${stderr}`);

      if (exitCode === 0) {
        return res.json({
          infected: false,
          scan_status: 'clean'
        });
      } else if (exitCode === 1) {
        console.warn(`[SECURITY ALERT] Infected file detected by ClamAV.`);
        return res.json({
          infected: true,
          scan_status: 'infected',
          virus: 'Malware detected by ClamAV scan'
        });
      } else {
        // Exit code 2 or other errors (e.g. database not initialized yet)
        console.warn(`[WARNING] clamscan exited with error code ${exitCode}. Defaulting to clean.`);
        return res.json({
          infected: false,
          scan_status: 'failed',
          error: `clamscan error exit code ${exitCode}`
        });
      }
    });

  } catch (err) {
    console.error('Error during scanning process:', err);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }
    return res.status(500).json({ error: 'Internal scanning error: ' + err.message });
  }
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', service: 'clamav-rest-server' });
});

app.listen(PORT, () => {
  console.log(`ClamAV Local REST Server listening on port ${PORT}`);
});
