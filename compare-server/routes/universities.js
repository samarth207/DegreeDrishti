const express = require('express');
const router = express.Router();
const University = require('../models/University');

// GET /api/universities — list with filters
router.get('/', async (req, res) => {
  try {
    const {
      search,
      minFee,
      maxFee,
      sortBy,       // 'fee_asc' | 'fee_desc' | 'rating' | 'ranking' | 'name'
      naac,
      type,
      ugcApproved,
      page = 1,
      limit = 20,
    } = req.query;

    const filter = {};

    if (search) {
      filter.$or = [
        { name: { $regex: search, $options: 'i' } },
        { shortName: { $regex: search, $options: 'i' } },
        { location: { $regex: search, $options: 'i' } },
      ];
    }
    if (minFee || maxFee) {
      filter.minFee = {};
      if (minFee) filter.minFee.$gte = Number(minFee);
      if (maxFee) filter.minFee.$lte = Number(maxFee);
    }
    if (naac) filter.naacGrade = naac;
    if (type) filter.type = type;
    if (ugcApproved === 'true') filter.ugcApproved = true;

    let sortOption = {};
    switch (sortBy) {
      case 'fee_asc':  sortOption = { minFee: 1 };           break;
      case 'fee_desc': sortOption = { minFee: -1 };          break;
      case 'rating':   sortOption = { rating: -1 };          break;
      case 'ranking':  sortOption = { 'ranking.nirf': 1 };   break;
      case 'name':     sortOption = { name: 1 };             break;
      default:         sortOption = { featured: -1, rating: -1 };
    }

    const skip = (Number(page) - 1) * Number(limit);
    const total = await University.countDocuments(filter);
    const universities = await University.find(filter)
      .sort(sortOption)
      .skip(skip)
      .limit(Number(limit))
      .select('-__v');

    res.json({ success: true, total, page: Number(page), data: universities });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/universities/compare?ids=id1,id2,id3
router.get('/compare', async (req, res) => {
  try {
    const { ids } = req.query;
    if (!ids) return res.status(400).json({ success: false, message: 'ids param required' });

    const idList = ids.split(',').slice(0, 5); // max 5
    const universities = await University.find({ _id: { $in: idList } }).select('-__v');
    res.json({ success: true, data: universities });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/universities/slugs?slugs=amity,manipal
router.get('/slugs', async (req, res) => {
  try {
    const { slugs } = req.query;
    if (!slugs) return res.status(400).json({ success: false, message: 'slugs param required' });

    const slugList = slugs.split(',').slice(0, 5);
    const universities = await University.find({ slug: { $in: slugList } }).select('-__v');
    res.json({ success: true, data: universities });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// GET /api/universities/:id
router.get('/:id', async (req, res) => {
  try {
    const uni = await University.findById(req.params.id).select('-__v');
    if (!uni) return res.status(404).json({ success: false, message: 'University not found' });
    res.json({ success: true, data: uni });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

module.exports = router;
