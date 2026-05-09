const express = require('express');
const cors    = require('cors');
const connectDB = require('./db');

const app = express();
const PORT = process.env.PORT || 5050;

// Middleware
app.use(cors());
app.use(express.json());

// Connect DB
connectDB();

// Routes
app.use('/api/universities', require('./routes/universities'));

// Health check
app.get('/api/health', (_, res) => res.json({ status: 'ok', service: 'DegreeDrishti Compare API' }));

app.listen(PORT, () => {
  console.log(`🚀 Compare API running on http://localhost:${PORT}`);
});
