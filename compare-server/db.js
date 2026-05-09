const mongoose = require('mongoose');

const MONGO_URI = 'mongodb+srv://CRM_DB:ZhIUxhzvC99GFr9b@crm.4dgei3o.mongodb.net/DegreeDrishti_Compare?appName=CRM';

const connectDB = async () => {
  try {
    await mongoose.connect(MONGO_URI);
    console.log('✅ MongoDB Connected — DB: DegreeDrishti_Compare');
  } catch (err) {
    console.error('❌ MongoDB connection error:', err.message);
    process.exit(1);
  }
};

module.exports = connectDB;
