const express = require('express');
const router = express.Router();
const { pool } = require('../config/database');

// Get all task statuses for kanban
router.get('/statuses', async (req, res) => {
    try {
        console.log('📋 Getting all task statuses...');
        
        const query = `
            SELECT id, title, key_name, color, sort, hide_from_kanban, deleted, hide_from_non_project_related_tasks
            FROM rise_task_status 
            WHERE deleted = 0 AND hide_from_kanban = 0 
            ORDER BY sort ASC
        `;
        
        const [rows] = await pool.execute(query);
        
        console.log(`✅ Found ${rows.length} statuses`);
        
        res.json({
            success: true,
            data: rows
        });
        
    } catch (error) {
        console.error('❌ Error getting statuses:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Add new status
router.post('/statuses', async (req, res) => {
    try {
        const { title, color = '#6c757d', project_id } = req.body;
        
        if (!title) {
            return res.status(400).json({
                success: false,
                error: 'Status title is required'
            });
        }
        
        console.log(`📝 Adding new status: ${title}`);
        
        // Generate key_name from title
        const key_name = title.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .trim();
        
        // Check if key_name already exists
        const [existing] = await pool.execute(
            'SELECT id FROM rise_task_status WHERE key_name = ? AND deleted = 0',
            [key_name]
        );
        
        if (existing.length > 0) {
            return res.status(400).json({
                success: false,
                error: 'Status with this name already exists'
            });
        }
        
        // Get next sort order
        const [maxSort] = await pool.execute(
            'SELECT MAX(sort) as max_sort FROM rise_task_status WHERE deleted = 0'
        );
        const sortOrder = (maxSort[0].max_sort || 0) + 1;
        
        // Insert new status
        const [result] = await pool.execute(`
            INSERT INTO rise_task_status 
            (title, key_name, color, sort, hide_from_kanban, deleted, hide_from_non_project_related_tasks) 
            VALUES (?, ?, ?, ?, 0, 0, 0)
        `, [title, key_name, color, sortOrder]);
        
        // Get the newly created status
        const [newStatus] = await pool.execute(
            'SELECT * FROM rise_task_status WHERE id = ?',
            [result.insertId]
        );
        
        console.log(`✅ Status added with ID: ${result.insertId}`);
        
        res.json({
            success: true,
            data: newStatus[0],
            message: 'Status added successfully'
        });
        
    } catch (error) {
        console.error('❌ Error adding status:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Reorder statuses (must come before /statuses/:id)
router.put('/statuses/reorder', async (req, res) => {
    try {
        const { status_orders } = req.body;
        
        if (!status_orders || !Array.isArray(status_orders)) {
            return res.status(400).json({
                success: false,
                error: 'Status orders array is required'
            });
        }
        
        console.log(`🔄 Reordering ${status_orders.length} statuses`);
        
        let successCount = 0;
        
        // Update each status sort order
        for (const order of status_orders) {
            if (order.id && typeof order.sort === 'number') {
                try {
                    await pool.execute(
                        'UPDATE rise_task_status SET sort = ? WHERE id = ?',
                        [order.sort, order.id]
                    );
                    successCount++;
                } catch (error) {
                    console.error(`❌ Error updating status ${order.id}:`, error);
                }
            }
        }
        
        console.log(`✅ Reordered ${successCount} statuses successfully`);
        
        if (successCount > 0) {
            res.json({
                success: true,
                message: `Reordered ${successCount} status(es) successfully`
            });
        } else {
            res.status(400).json({
                success: false,
                error: 'Failed to reorder statuses'
            });
        }
        
    } catch (error) {
        console.error('❌ Error reordering statuses:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Update status
router.put('/statuses/:id', async (req, res) => {
    try {
        const statusId = parseInt(req.params.id);
        const { title, color } = req.body;
        
        if (!statusId || !title) {
            return res.status(400).json({
                success: false,
                error: 'Status ID and title are required'
            });
        }
        
        console.log(`📝 Updating status ${statusId}: ${title}`);
        
        // Check if status exists
        const [existing] = await pool.execute(
            'SELECT * FROM rise_task_status WHERE id = ? AND deleted = 0',
            [statusId]
        );
        
        if (existing.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Status not found'
            });
        }
        
        const status = existing[0];
        const updateData = {
            title: title,
            color: color || status.color
        };
        
        // Generate new key_name if title changed
        const new_key_name = title.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .trim();
            
        if (new_key_name !== status.key_name) {
            // Check if new key_name already exists
            const [keyExists] = await pool.execute(
                'SELECT id FROM rise_task_status WHERE key_name = ? AND deleted = 0 AND id != ?',
                [new_key_name, statusId]
            );
            
            if (keyExists.length === 0) {
                updateData.key_name = new_key_name;
            }
        }
        
        // Build update query
        const fields = Object.keys(updateData);
        const values = Object.values(updateData);
        const setClause = fields.map(field => `${field} = ?`).join(', ');
        
        await pool.execute(
            `UPDATE rise_task_status SET ${setClause} WHERE id = ?`,
            [...values, statusId]
        );
        
        // Get updated status
        const [updatedStatus] = await pool.execute(
            'SELECT * FROM rise_task_status WHERE id = ?',
            [statusId]
        );
        
        console.log(`✅ Status ${statusId} updated successfully`);
        
        res.json({
            success: true,
            data: updatedStatus[0],
            message: 'Status updated successfully'
        });
        
    } catch (error) {
        console.error('❌ Error updating status:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Delete status (soft delete)
router.delete('/statuses/:id', async (req, res) => {
    try {
        const statusId = parseInt(req.params.id);
        
        if (!statusId) {
            return res.status(400).json({
                success: false,
                error: 'Status ID is required'
            });
        }
        
        console.log(`🗑️ Deleting status ${statusId}`);
        
        // Check if status exists
        const [existing] = await pool.execute(
            'SELECT * FROM rise_task_status WHERE id = ? AND deleted = 0',
            [statusId]
        );
        
        if (existing.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Status not found'
            });
        }
        
        // Check if status is being used by tasks
        const [tasksCount] = await pool.execute(
            'SELECT COUNT(*) as count FROM rise_tasks WHERE status_id = ? AND deleted = 0',
            [statusId]
        );
        
        if (tasksCount[0].count > 0) {
            return res.status(400).json({
                success: false,
                error: `Cannot delete status. It is being used by ${tasksCount[0].count} task(s). Please move tasks to another status first.`
            });
        }
        
        // Soft delete
        await pool.execute(
            'UPDATE rise_task_status SET deleted = 1 WHERE id = ?',
            [statusId]
        );
        
        console.log(`✅ Status ${statusId} deleted successfully`);
        
        res.json({
            success: true,
            message: 'Status deleted successfully'
        });
        
    } catch (error) {
        console.error('❌ Error deleting status:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});



// Toggle status visibility in kanban
router.put('/statuses/:id/visibility', async (req, res) => {
    try {
        const statusId = parseInt(req.params.id);
        const { hide_from_kanban } = req.body;
        
        if (!statusId) {
            return res.status(400).json({
                success: false,
                error: 'Status ID is required'
            });
        }
        
        const hideValue = hide_from_kanban ? 1 : 0;
        console.log(`👁️ Setting status ${statusId} visibility: ${hideValue ? 'hidden' : 'visible'}`);
        
        await pool.execute(
            'UPDATE rise_task_status SET hide_from_kanban = ? WHERE id = ?',
            [hideValue, statusId]
        );
        
        const action = hideValue ? 'hidden from' : 'shown in';
        console.log(`✅ Status ${statusId} ${action} kanban board`);
        
        res.json({
            success: true,
            message: `Status ${action} kanban board`
        });
        
    } catch (error) {
        console.error('❌ Error toggling status visibility:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Get status statistics
router.get('/statuses/stats', async (req, res) => {
    try {
        const { project_id } = req.query;
        
        console.log('📊 Getting status statistics...');
        
        let query = `
            SELECT 
                s.id,
                s.title,
                s.key_name,
                s.color,
                s.sort,
                COUNT(t.id) as task_count
            FROM rise_task_status s
            LEFT JOIN rise_tasks t ON s.id = t.status_id AND t.deleted = 0
            WHERE s.deleted = 0
        `;
        
        const params = [];
        
        if (project_id) {
            query += ' AND (t.project_id = ? OR t.project_id IS NULL)';
            params.push(project_id);
        }
        
        query += ' GROUP BY s.id ORDER BY s.sort ASC';
        
        const [rows] = await pool.execute(query, params);
        
        console.log(`✅ Retrieved statistics for ${rows.length} statuses`);
        
        res.json({
            success: true,
            data: rows
        });
        
    } catch (error) {
        console.error('❌ Error getting status statistics:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

module.exports = router;