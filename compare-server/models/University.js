const mongoose = require('mongoose');

const courseSchema = new mongoose.Schema({
  name: String,                 // e.g. "MBA"
  duration: String,             // e.g. "2 Years"
  fee: Number,                  // Annual fee in INR
  totalFee: Number,             // Total program fee in INR
  specializations: [String],
});

const universitySchema = new mongoose.Schema({
  slug: { type: String, unique: true, required: true },
  name: { type: String, required: true },
  shortName: String,
  logo: String,                 // URL or path
  established: Number,
  location: String,
  type: String,                 // "Private" | "Government" | "Deemed"
  naacGrade: String,            // "A++" | "A+" | "A" | "B++"
  ugcApproved: Boolean,
  ranking: {
    nirf: Number,
    qs: Number,
    outlook: Number,
  },
  courses: [courseSchema],
  minFee: Number,               // Lowest annual fee across all courses
  maxFee: Number,               // Highest annual fee
  admissionMode: String,        // "Online" | "Offline" | "Both"
  examAccepted: [String],       // ["CAT", "MAT", "Self Test"]
  highlights: [String],
  placementRate: Number,        // percentage
  avgSalary: Number,            // in LPA
  topRecruiters: [String],
  emiAvailable: Boolean,
  scholarshipAvailable: Boolean,
  lmsType: String,
  supportType: [String],        // ["Email", "Phone", "Chat"]
  websiteUrl: String,
  rating: Number,               // out of 5
  reviewCount: Number,
  featured: { type: Boolean, default: false },
}, { timestamps: true });

module.exports = mongoose.model('University', universitySchema);
